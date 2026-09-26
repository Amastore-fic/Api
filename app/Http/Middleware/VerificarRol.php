<?php

namespace App\Http\Middleware;

use App\Enums\RolUsuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Restringe una ruta a uno o varios roles: ->middleware('rol:vendedor,administrador').
// Tambien corta el paso a cuentas bloqueadas aunque tengan un token vigente.
class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        if (! $usuario->activo) {
            return response()->json(['message' => 'La cuenta esta bloqueada.'], 403);
        }

        $permitidos = array_map(fn (string $rol) => RolUsuario::from($rol), $roles);

        if (! $usuario->tieneRol(...$permitidos)) {
            return response()->json(['message' => 'No tienes permiso para esta operacion.'], 403);
        }

        return $next($request);
    }
}
