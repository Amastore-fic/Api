<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PedidoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // TODO: todos los pedidos del sistema, filtrables por estado y vendedor
        return $this->pendiente('supervision de pedidos');
    }
}
