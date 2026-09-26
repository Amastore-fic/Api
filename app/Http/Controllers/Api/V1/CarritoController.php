<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarritoController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        // TODO: firstOrCreate del carrito del cliente; devolver items con producto y total()
        return $this->pendiente('consultar carrito');
    }

    public function agregar(Request $request): JsonResponse
    {
        // TODO: validar id_producto (activo) y cantidad (>= 1, <= existencias);
        //       si la linea ya existe, sumar cantidades
        return $this->pendiente('agregar producto al carrito');
    }

    public function actualizar(Request $request, int $id): JsonResponse
    {
        // TODO: cambiar cantidad de una linea del carrito del cliente en sesion
        return $this->pendiente('modificar cantidad');
    }

    public function eliminar(Request $request, int $id): JsonResponse
    {
        // TODO: borrar la linea (solo si pertenece al carrito del cliente en sesion)
        return $this->pendiente('eliminar linea del carrito');
    }
}
