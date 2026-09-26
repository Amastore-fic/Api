<?php

namespace App\Services;

use App\Models\Pedido;
use App\Services\Pagos\PasarelaPago;

class PagoService
{
    public function __construct(private readonly PasarelaPago $pasarela) {}

    public function cobrar(Pedido $pedido): Pedido
    {
        // TODO (Funcionalidad 6 - Pago simulado), todo dentro de DB::transaction:
        //  1. Verificar que el pedido este en pendiente_pago
        //  2. $resultado = $this->pasarela->cobrar($pedido) y registrar el Pago
        //  3. Aprobado: volver a validar existencias con lockForUpdate(), descontarlas,
        //     marcar el pedido como pagado (pagado_en = now()) y vaciar el carrito
        //  4. Rechazado: registrar el pago rechazado; no tocar inventario ni carrito
        throw new \LogicException('PagoService::cobrar pendiente de implementar.');
    }
}
