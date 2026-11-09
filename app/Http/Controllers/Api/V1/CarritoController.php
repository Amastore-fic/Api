<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\AgregarAlCarritoRequest;
use App\Http\Requests\Cliente\CambiarCantidadRequest;
use App\Services\CarritoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Todas las rutas responden el carrito completo, asi la app no tiene que volver a pedirlo
class CarritoController extends Controller
{
    public function __construct(private CarritoService $carritos) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json($this->carritos->detalle($this->carritos->de($request->user())));
    }

    public function agregar(AgregarAlCarritoRequest $request): JsonResponse
    {
        $carrito = $this->carritos->agregar(
            $request->user(),
            (int) $request->id_producto,
            (int) $request->input('cantidad', 1),
        );

        return response()->json($this->carritos->detalle($carrito));
    }

    public function actualizar(CambiarCantidadRequest $request, int $id): JsonResponse
    {
        $carrito = $this->carritos->cambiarCantidad($request->user(), $id, (int) $request->cantidad);

        return response()->json($this->carritos->detalle($carrito));
    }

    public function eliminar(Request $request, int $id): JsonResponse
    {
        $carrito = $this->carritos->eliminar($request->user(), $id);

        return response()->json($this->carritos->detalle($carrito));
    }
}
