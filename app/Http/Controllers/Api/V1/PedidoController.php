<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PedidoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PedidoController extends Controller
{
    public function __construct(private readonly PedidoService $pedidos) {}

    // POST /pedidos { id_direccion }
    public function store(Request $request): JsonResponse
    {
        // TODO: $this->pedidos->crearDesdeCarrito($request->user(), $idDireccion)
        //       Flujo alterno: existencias insuficientes -> 422 con el producto afectado
        return $this->pendiente('generar pedido desde el carrito');
    }

    public function index(Request $request): JsonResponse
    {
        // TODO: pedidos del cliente en sesion, mas recientes primero
        return $this->pendiente('listar pedidos del cliente');
    }

    // Accesible por cliente (el suyo), vendedor (si le corresponde) y administrador
    public function show(Request $request, int $id): JsonResponse
    {
        // TODO: cargar pedido y autorizar con PedidoPolicy::view
        return $this->pendiente('consultar pedido');
    }
}
