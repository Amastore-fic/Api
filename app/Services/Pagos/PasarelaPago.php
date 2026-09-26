<?php

namespace App\Services\Pagos;

use App\Models\Pedido;

// Contrato que cumple cualquier pasarela. El resto del sistema depende solo
// de esta interfaz (mitigacion del riesgo "Integracion con pasarela de pago").
interface PasarelaPago
{
    public function nombre(): string;

    public function cobrar(Pedido $pedido): ResultadoPago;
}
