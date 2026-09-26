<?php

namespace Tests\Unit;

use App\Models\Pedido;
use App\Services\Pagos\PasarelaSimulada;
use Tests\TestCase;

class PasarelaSimuladaTest extends TestCase
{
    public function test_aprueba_montos_menores_al_limite(): void
    {
        $pedido = new Pedido(['total' => 999.99]);

        $resultado = (new PasarelaSimulada(1000))->cobrar($pedido);

        $this->assertTrue($resultado->aprobado);
        $this->assertStringStartsWith('SIM-', $resultado->referencia);
    }

    public function test_rechaza_montos_desde_el_limite(): void
    {
        $pedido = new Pedido(['total' => 1000]);

        $this->assertFalse((new PasarelaSimulada(1000))->cobrar($pedido)->aprobado);
    }
}
