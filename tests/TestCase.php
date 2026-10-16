<?php

namespace Tests;

use App\Services\BitacoraService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;
use Tests\Fakes\BitacoraEnMemoria;

abstract class TestCase extends BaseTestCase
{
    // Bitacora en memoria para todas las pruebas: no se escribe en MongoDB
    protected BitacoraEnMemoria $bitacora;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bitacora = new BitacoraEnMemoria;
        $this->app->instance(BitacoraService::class, $this->bitacora);
    }

    // Candado: las pruebas con RefreshDatabase borran la base completa. Antes de que
    // cualquier trait toque la base, se verifica que sea la temporal en memoria; si no,
    // la prueba se detiene sin borrar nada (por ejemplo, si el contenedor impone pgsql).
    protected function setUpTraits()
    {
        $conexion = config('database.default');
        $base = config("database.connections.{$conexion}.database");

        if ($conexion !== 'sqlite' || $base !== ':memory:') {
            throw new RuntimeException(
                "Pruebas detenidas para proteger la base \"{$base}\" ({$conexion}): "
                .'deben correr con SQLite en memoria. Revisa phpunit.xml.'
            );
        }

        return parent::setUpTraits();
    }
}
