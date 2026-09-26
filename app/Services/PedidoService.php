<?php

namespace App\Services;

use App\Models\Pedido;
use App\Models\Usuario;

class PedidoService
{
    public function crearDesdeCarrito(Usuario $cliente, int $idDireccion): Pedido
    {
        // TODO (Funcionalidad 5 - Confirmacion de compra):
        //  1. Validar que la direccion pertenece al cliente
        //  2. Validar que el carrito no este vacio y que todos sus productos sean
        //     del mismo vendedor (supuesto del MVP)
        //  3. Validar existencias de cada linea; si falta alguna, lanzar
        //     ExistenciasInsuficientes con el producto afectado (se responde 422)
        //  4. Crear Pedido (folio = Str::uuid(), estado pendiente_pago, copia de la
        //     direccion en direccion_envio) y sus PedidoDetalle con precio actual
        //  Las existencias NO se descuentan aqui, sino cuando el pago se aprueba.
        throw new \LogicException('PedidoService::crearDesdeCarrito pendiente de implementar.');
    }
}
