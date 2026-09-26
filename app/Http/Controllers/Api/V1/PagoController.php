<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    public function __construct(private readonly PagoService $pagos) {}

    // POST /pedidos/{id}/pago
    public function pagar(Request $request, int $id): JsonResponse
    {
        // TODO: $this->pagos->cobrar($pedido)
        //       Aprobado  -> pedido pagado, se descuentan existencias y se vacia el carrito
        //       Rechazado -> no hay pedido pagado, no se toca inventario, el carrito se conserva
        return $this->pendiente('pagar pedido');
    }

    // POST /pagos/notificacion (lo llama la pasarela, no un usuario)
    public function notificacion(Request $request): JsonResponse
    {
        // TODO: validar firma de la pasarela y actualizar el estado del pago
        return $this->pendiente('notificacion de la pasarela');
    }
}
