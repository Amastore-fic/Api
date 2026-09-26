<?php

namespace App\Http\Controllers\Api\V1\Vendedor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendedor\GuardarProductoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Publicaciones del vendedor en sesion. Un vendedor solo puede tocar sus propios productos.
class ProductoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // TODO: listar $request->user()->vendedor->productos (incluye retirados)
        return $this->pendiente('listar productos del vendedor');
    }

    public function store(GuardarProductoRequest $request): JsonResponse
    {
        // TODO: crear producto con id_vendedor del usuario en sesion; queda visible en el catalogo
        return $this->pendiente('publicar producto');
    }

    public function update(GuardarProductoRequest $request, int $id): JsonResponse
    {
        // TODO: verificar que el producto pertenece al vendedor (403 si no) y actualizar
        return $this->pendiente('editar producto');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        // TODO: baja logica (estado = retirado); no borrar para no romper pedidos historicos
        return $this->pendiente('retirar producto');
    }
}
