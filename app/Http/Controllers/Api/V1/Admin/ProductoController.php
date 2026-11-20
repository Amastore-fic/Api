<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\EstadoProducto;
use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'buscar'       => ['nullable', 'string', 'max:100'],
            'estado'       => ['nullable', Rule::enum(EstadoProducto::class)],
            'id_categoria' => ['nullable', 'integer', 'exists:categorias,id'],
            'por_pagina'   => ['nullable', 'integer', 'in:6,12,15,24'],
        ]);

        $productos = Producto::query()
            ->with(['categoria', 'vendedor.usuario'])
            ->when($request->estado, fn ($q, $estado) => $q->where('estado', $estado))
            ->when($request->id_categoria, fn ($q, $catId) => $q->where('id_categoria', $catId))
            ->when($request->buscar, function ($q, $buscar) {
                $q->where(function ($sub) use ($buscar) {
                    $sub->where('titulo', 'ilike', "%{$buscar}%")
                        ->orWhere('descripcion', 'ilike', "%{$buscar}%")
                        ->orWhereHas('vendedor.usuario', function ($u) use ($buscar) {
                            $u->where('nombre', 'ilike', "%{$buscar}%")
                                ->orWhere('apellido', 'ilike', "%{$buscar}%")
                                ->orWhere('correo', 'ilike', "%{$buscar}%");
                        });
                });
            })
            ->latest('id')
            ->paginate($request->integer('por_pagina', 15));

        return response()->json($productos);
    }

    public function cambiarEstado(Request $request, int $id): JsonResponse
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::enum(EstadoProducto::class)],
        ]);

        $producto = Producto::findOrFail($id);
        $producto->estado = EstadoProducto::from($datos['estado']);
        $producto->save();

        $producto->load(['categoria', 'vendedor.usuario']);

        return response()->json([
            'message'  => $producto->estado === EstadoProducto::Activo
                ? 'Producto reactivado.'
                : 'Producto desactivado.',
            'producto' => $producto,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $producto = Producto::findOrFail($id);
        $producto->estado = EstadoProducto::Retirado;
        $producto->save();

        return response()->json([
            'message'  => 'Producto retirado.',
            'producto' => $producto->load(['categoria', 'vendedor.usuario']),
        ]);
    }
}