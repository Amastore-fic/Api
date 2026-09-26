<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalificacionController extends Controller
{
    public function index(int $id): JsonResponse
    {
        // TODO: calificaciones del producto con nombre del cliente, paginadas
        return $this->pendiente('listar calificaciones');
    }

    public function store(Request $request, int $id): JsonResponse
    {
        // TODO: puntuacion 1..5 y comentario opcional.
        //       Responder 403 si el cliente no tiene un pedido pagado que incluya el producto
        //       (compra verificada) y 409 si ya lo califico.
        return $this->pendiente('calificar producto');
    }
}
