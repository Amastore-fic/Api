<?php

namespace App\Services;

use App\Enums\EventoBitacora as Evento;
use App\Models\EventoBitacora;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

// Servicio de escritura de la bitacora (MongoDB).
//
// Reglas:
// - Nunca se guardan contrasenas ni tokens.
// - Del correo solo se guarda un hash: sirve para agrupar intentos contra una misma
//   cuenta sin almacenar el correo en claro.
// - Si MongoDB falla, la operacion del usuario (por ejemplo, iniciar sesion) sigue:
//   el error se anota en el log de Laravel y no se propaga.
class BitacoraService
{
    public function registrar(
        Evento $evento,
        Request $request,
        ?Usuario $usuario = null,
        array $detalles = [],
    ): void {
        try {
            $this->guardar($this->documento($evento, $request, $usuario, $detalles));
        } catch (Throwable $e) {
            Log::warning('No se pudo escribir en la bitácora de MongoDB.', [
                'evento' => $evento->value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // Arma el documento que se guarda (publico para poder revisarlo en pruebas)
    public function documento(
        Evento $evento,
        Request $request,
        ?Usuario $usuario = null,
        array $detalles = [],
    ): array {
        $correo = $usuario?->correo ?? $request->input('correo');

        $documento = [
            'evento' => $evento->value,
            'id_usuario' => $usuario?->id,
            'rol' => $usuario?->rol?->value,
            'correo_hash' => is_string($correo) && $correo !== ''
                ? hash('sha256', Str::lower(trim($correo)))
                : null,
            'ip' => $request->ip(),
            'dispositivo' => Str::limit((string) $request->input('dispositivo'), 100, ''),
            'agente' => Str::limit((string) $request->userAgent(), 255, ''),
            'detalles' => $detalles,
            'fecha' => now(),
        ];

        // No se guardan campos vacios
        return array_filter($documento, fn ($valor) => $valor !== null && $valor !== '' && $valor !== []);
    }

    protected function guardar(array $documento): void
    {
        EventoBitacora::create($documento);
    }
}
