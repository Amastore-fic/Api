<?php

namespace App\Http\Controllers\Api\V1\Vendedor;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PedidoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // TODO: pedidos donde id_vendedor = vendedor en sesion, con detalles y paginados
        return $this->pendiente('pedidos del vendedor');
    }
}
