<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
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
