<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CatalogoService
{
    public function buscar(array $filtros): LengthAwarePaginator
    {
        return Producto::visibles()
            ->with(['categoria:id,nombre', 'vendedor:id,nombre_comercial,codigo_vendedor'])
            ->withAvg('calificaciones', 'puntuacion')
            ->when($filtros['buscar'] ?? null, fn ($q, $texto) => $q->whereLike('titulo', "%{$texto}%"))
            ->when($filtros['categoria'] ?? null, fn ($q, $categoria) => $q->where('id_categoria', $categoria))
            ->latest()
            ->paginate($filtros['por_pagina'] ?? 20);
    }

    public function ficha(int $id): Producto
    {
        return Producto::visibles()
            ->with([
                'categoria:id,nombre',
                'vendedor:id,nombre_comercial,codigo_vendedor,sucursal,descripcion',
            ])
            ->withAvg('calificaciones', 'puntuacion')
            ->withCount('calificaciones')
            ->findOrFail($id);
    }

    public function categorias(): Collection
    {
        return Categoria::orderBy('nombre')->get(['id', 'nombre', 'slug']);
    }
}
