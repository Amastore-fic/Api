<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CatalogoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Endpoints publicos del catalogo: no requieren sesion.
class CatalogoController extends Controller
{
    public function __construct(private readonly CatalogoService $catalogo) {}

    // GET /productos?buscar=texto&categoria=id&por_pagina=20
    public function productos(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'categoria' => ['nullable', 'integer', 'exists:categorias,id'],
            'por_pagina' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json($this->catalogo->buscar($filtros));
    }

    // GET /productos/{id}: ficha con vendedor identificado, existencias y calificaciones
    public function producto(int $id): JsonResponse
    {
        return response()->json($this->catalogo->ficha($id));
    }

    public function categorias(): JsonResponse
    {
        return response()->json($this->catalogo->categorias());
    }
}
