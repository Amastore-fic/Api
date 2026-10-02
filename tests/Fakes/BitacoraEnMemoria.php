<?php

namespace Tests\Fakes;

use App\Services\BitacoraService;

// Bitacora para pruebas: guarda los documentos en un arreglo en lugar de MongoDB.
// Tests\TestCase la instala en todas las pruebas, asi ninguna escribe en la MongoDB
// de desarrollo ni necesita que MongoDB este encendido.
class BitacoraEnMemoria extends BitacoraService
{
    /** @var array<int, array> */
    public array $documentos = [];

    protected function guardar(array $documento): void
    {
        $this->documentos[] = $documento;
    }

    /** Documentos de un tipo de evento */
    public function deEvento(string $evento): array
    {
        return array_values(array_filter($this->documentos, fn ($d) => $d['evento'] === $evento));
    }
}
