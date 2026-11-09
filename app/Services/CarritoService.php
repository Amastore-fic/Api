<?php

namespace App\Services;

use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Carrito del cliente (HU-21 y HU-22). Precios, subtotales y total los calcula siempre
// el servidor; el cliente solo manda que producto y cuantas piezas.
class CarritoService
{
    public function de(Usuario $cliente): Carrito
    {
        return Carrito::firstOrCreate(['id_usuario' => $cliente->id]);
    }

    public function agregar(Usuario $cliente, int $idProducto, int $cantidad): Carrito
    {
        $carrito = $this->de($cliente);

        DB::transaction(function () use ($carrito, $idProducto, $cantidad) {
            $producto = Producto::visibles()->lockForUpdate()->find($idProducto);
            if (! $producto) {
                throw ValidationException::withMessages(['id_producto' => 'Este producto ya no está disponible.']);
            }

            // Si ya estaba en el carrito se suman las piezas en la misma linea
            $item = $carrito->items()->where('id_producto', $idProducto)->lockForUpdate()->first();
            $total = ($item?->cantidad ?? 0) + $cantidad;
            $this->validarExistencias($producto, $total, $item?->cantidad ?? 0);

            $item
                ? $item->update(['cantidad' => $total])
                : $carrito->items()->create(['id_producto' => $idProducto, 'cantidad' => $total]);
        });

        return $carrito;
    }

    // Busca la linea entre las del cliente: la de otro carrito responde 404, igual que una que no existe
    public function cambiarCantidad(Usuario $cliente, int $idItem, int $cantidad): Carrito
    {
        $carrito = $this->de($cliente);
        $item = $carrito->items()->findOrFail($idItem);

        $producto = Producto::visibles()->find($item->id_producto);
        if (! $producto) {
            throw ValidationException::withMessages(['cantidad' => 'Este producto ya no está disponible; quítalo del carrito.']);
        }
        $this->validarExistencias($producto, $cantidad);

        $item->update(['cantidad' => $cantidad]);

        return $carrito;
    }

    public function eliminar(Usuario $cliente, int $idItem): Carrito
    {
        $carrito = $this->de($cliente);
        $carrito->items()->findOrFail($idItem)->delete();

        return $carrito;
    }

    // Lo que responden todas las rutas del carrito
    public function detalle(Carrito $carrito): array
    {
        $carrito->load([
            'items' => fn ($q) => $q->orderBy('id'),
            'items.producto.categoria:id,nombre',
            'items.producto.vendedor:id,id_usuario,nombre_comercial,codigo_vendedor',
            'items.producto.vendedor.usuario:id,activo',
        ]);

        return [
            'id' => $carrito->id,
            'items' => $carrito->items->map(fn (CarritoItem $item) => [
                'id' => $item->id,
                'id_producto' => $item->id_producto,
                'cantidad' => $item->cantidad,
                // Retirado o de un vendedor bloqueado: se muestra, pero no cuenta en el total
                'disponible' => $item->producto->disponibleParaComprar(),
                'subtotal' => number_format($item->cantidad * $item->producto->precio, 2, '.', ''),
                'producto' => [
                    'id' => $item->producto->id,
                    'titulo' => $item->producto->titulo,
                    'descripcion' => $item->producto->descripcion,
                    'precio' => $item->producto->precio,
                    'existencias' => $item->producto->existencias,
                    'imagen_url' => $item->producto->imagen_url,
                    'categoria' => $item->producto->categoria?->only(['id', 'nombre']),
                    'vendedor' => $item->producto->vendedor?->only(['id', 'nombre_comercial', 'codigo_vendedor']),
                ],
            ])->values(),
            'total' => $carrito->total(),
            'cantidad_articulos' => $carrito->items
                ->filter(fn (CarritoItem $item) => $item->producto->disponibleParaComprar())
                ->sum('cantidad'),
        ];
    }

    private function validarExistencias(Producto $producto, int $cantidad, int $yaEnCarrito = 0): void
    {
        if ($cantidad <= $producto->existencias) {
            return;
        }

        $mensaje = $yaEnCarrito > 0
            ? "Solo hay {$producto->existencias} disponibles y ya tienes {$yaEnCarrito} en tu carrito."
            : "Solo hay {$producto->existencias} disponibles.";

        throw ValidationException::withMessages(['cantidad' => $mensaje]);
    }
}
