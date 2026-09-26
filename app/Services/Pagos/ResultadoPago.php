<?php

namespace App\Services\Pagos;

final readonly class ResultadoPago
{
    public function __construct(
        public bool $aprobado,
        public string $referencia,
        public string $mensaje,
        public array $respuestaCruda = [],
    ) {}
}
