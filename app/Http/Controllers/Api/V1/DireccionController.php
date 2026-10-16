<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\GuardarDireccionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DireccionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($request->user()->direcciones()->latest()->get());
    }

    public function store(GuardarDireccionRequest $request): JsonResponse
    {
        $direccion = $request->user()->direcciones()->create($request->validated());

        return response()->json($direccion, 201);
    }

    // Se busca entre las direcciones del cliente: la de otro responde 404, igual que una que no existe
    public function update(GuardarDireccionRequest $request, int $id): JsonResponse
    {
        $direccion = $request->user()->direcciones()->findOrFail($id);
        $direccion->update($request->validated());

        return response()->json($direccion);
    }

    // Borrado logico: pedidos.id_direccion sigue apuntando a un registro valido
    // y la direccion deja de aparecer en el listado y de poder usarse en pedidos nuevos.
    public function destroy(Request $request, int $id): JsonResponse
    {
        $request->user()->direcciones()->findOrFail($id)->delete();

        return response()->json(['message' => 'Dirección eliminada.']);
    }
}
