<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DireccionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($request->user()->direcciones()->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'destinatario' => ['required', 'string', 'max:255'],
            'calle' => ['required', 'string', 'max:255'],
            'numero_exterior' => ['required', 'string', 'max:20'],
            'numero_interior' => ['nullable', 'string', 'max:20'],
            'colonia' => ['required', 'string', 'max:255'],
            'ciudad' => ['required', 'string', 'max:255'],
            'estado' => ['required', 'string', 'max:255'],
            'codigo_postal' => ['required', 'string', 'max:10'],
            'telefono' => ['required', 'string', 'max:15'],
            'referencias' => ['nullable', 'string', 'max:1000'],
        ]);

        $direccion = $request->user()->direcciones()->create($datos);

        return response()->json($direccion, 201);
    }
}
