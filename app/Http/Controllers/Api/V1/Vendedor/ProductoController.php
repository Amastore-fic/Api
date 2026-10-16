<?php

namespace App\Http\Controllers\Api\V1\Vendedor;

use App\Enums\EstadoProducto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendedor\GuardarProductoRequest;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductoController extends Controller
{
    /**
     * Listar las publicaciones del vendedor (activas y retiradas)
     */
    public function index(Request $request): JsonResponse
    {
        $vendedor = $this->obtenerVendedor($request);

        if (!$vendedor) {
            return $this->errorRespuesta('El usuario no tiene perfil de vendedor.', 403);
        }

        $productos = $vendedor->productos()->with('categoria')->latest()->get();

        return response()->json($productos);
    }

    /**
     * Crear una nueva publicación
     */
    public function store(GuardarProductoRequest $request): JsonResponse
    {
        $vendedor = $this->obtenerVendedor($request);

        if (!$vendedor) {
            return $this->errorRespuesta('El usuario no tiene perfil de vendedor.', 403);
        }

        $datos = $request->validated();

        // Procesar la subida de imagen a la carpeta local 'productos' dentro de storage/app/public
        if ($request->hasFile('imagen')) {
            $datos['imagen_url'] = $request->file('imagen')->store('productos', 'public');
        }

        // Remover la propiedad del archivo binario antes de insertar en BD
        unset($datos['imagen']);
        $datos['estado'] = EstadoProducto::Activo;

        $producto = $vendedor->productos()->create($datos);

        return response()->json([
            'message'  => 'Producto creado exitosamente.',
            'producto' => $producto->load('categoria'),
        ], 201);
    }

    /**
     * Actualizar una publicación existente
     */
    public function update(GuardarProductoRequest $request, int $id): JsonResponse
    {
        $vendedor = $this->obtenerVendedor($request);

        if (!$vendedor) {
            return $this->errorRespuesta('El usuario no tiene perfil de vendedor.', 403);
        }

        $producto = Producto::find($id);

        if (!$producto) {
            return $this->errorRespuesta('Producto no encontrado.', 404);
        }

        if ($producto->id_vendedor !== $vendedor->id) {
            return $this->errorRespuesta('No tienes permiso para modificar este producto.', 403);
        }

        $datos = $request->validated();

        // Si se seleccionó una imagen nueva
        if ($request->hasFile('imagen')) {
            // Eliminar la imagen anterior del disco público
            $imagenAnterior = $producto->getRawOriginal('imagen_url');
            if ($imagenAnterior && Storage::disk('public')->exists($imagenAnterior)) {
                Storage::disk('public')->delete($imagenAnterior);
            }

            // Guardar el nuevo archivo
            $datos['imagen_url'] = $request->file('imagen')->store('productos', 'public');
        }

        // Remover el campo del archivo binario para no alterar la BD
        unset($datos['imagen']);

        $producto->update($datos);

        return response()->json([
            'message'  => 'Producto actualizado exitosamente.',
            'producto' => $producto->fresh(['categoria']),
        ]);
    }

    /**
     * Baja lógica (marcar como retirado)
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $vendedor = $this->obtenerVendedor($request);

        if (!$vendedor) {
            return $this->errorRespuesta('El usuario no tiene perfil de vendedor.', 403);
        }

        $producto = Producto::find($id);

        if (!$producto) {
            return $this->errorRespuesta('Producto no encontrado.', 404);
        }

        if ($producto->id_vendedor !== $vendedor->id) {
            return $this->errorRespuesta('No tienes permiso para modificar este producto.', 403);
        }

        $producto->update(['estado' => EstadoProducto::Retirado]);

        return response()->json([
            'message'  => 'Producto retirado correctamente.',
            'producto' => $producto,
        ]);
    }

    /* -------------------------------------------------------------------------- */
    /*                             MÉTODOS AUXILIARES                             */
    /* -------------------------------------------------------------------------- */

    private function obtenerVendedor(Request $request)
    {
        return $request->user()?->vendedor;
    }

    private function errorRespuesta(string $mensaje, int $codigo): JsonResponse
    {
        return response()->json(['message' => $mensaje], $codigo);
    }
}