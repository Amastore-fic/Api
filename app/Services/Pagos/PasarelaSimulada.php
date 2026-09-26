<?php

namespace App\Services\Pagos;

use App\Models\Pedido;
use Illuminate\Support\Str;

// Simula el actor externo "Banco": no mueve dinero real.
// Aprueba todo cobro cuyo total sea menor a config('pagos.simulada.limite_rechazo')
// y rechaza el resto, para poder demostrar el flujo alterno de pago rechazado.
class PasarelaSimulada implements PasarelaPago
{
    public function __construct(private readonly float $limiteRechazo) {}

    public function nombre(): string
    {
        return 'simulada';
    }

    public function cobrar(Pedido $pedido): ResultadoPago
    {
        $aprobado = (float) $pedido->total < $this->limiteRechazo;
        $referencia = 'SIM-'.Str::upper(Str::random(12));

        return new ResultadoPago(
            aprobado: $aprobado,
            referencia: $referencia,
            mensaje: $aprobado ? 'Pago aprobado (simulado).' : 'Pago rechazado por la pasarela simulada.',
            respuestaCruda: [
                'referencia' => $referencia,
                'estado' => $aprobado ? 'approved' : 'declined',
                'monto' => $pedido->total,
            ],
        );
    }
}
