<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\EventoBitacora;
use App\Enums\RolUsuario;
use App\Models\Usuario;
use App\Services\BitacoraService;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{

    //GET /admin/usuarios?rol=cliente&buscar=william&page=1
    public function index(Request $request): JsonResponse
    {
        // TODO: usuarios y vendedores, filtrables por rol y paginados
        

        $request->validate([
            'rol' => ['nullable', Rule::enum(RolUsuario::class)],
            'buscar' => ['nullable', 'string', 'max:100'],
            'activo' => ['nullable', 'boolean'],
            'por_pagina' => ['nullable', 'integer', 'in:6,12,24'],
        ]);

        $usuarios = Usuario::query()
            ->with('vendedor')
            ->when($request->rol, fn ($q, $rol) => $q->where('rol', $rol))
            ->when($request->filled('activo'), fn ($q) => $q->where('activo', $request->boolean('activo')))
            ->when($request->buscar, function ($q, $buscar) {
                $q->where(fn ($q) => $q
                    ->where('nombre', 'ilike', "%{$buscar}%")
                    ->orWhere('apellido', 'ilike', "%{$buscar}%")
                    ->orWhere('correo', 'ilike', "%{$buscar}%"));
            })
            ->orderBy('nombre') //ordena alfabeticamente por nombre en la lista
            ->paginate($request->integer('por_pagina', 15)); //se muestra de 15 en 15, con el parametro page
        
        return response()->json($usuarios);
    }

    // PATCH /admin/usuarios/{id}/estado { activo: bool }
    public function cambiarEstado(Request $request, int $id, BitacoraService $bitacora): JsonResponse
    {
        $request->validate(['activo' => ['required', 'boolean']]);

        $usuario = Usuario::findOrFail($id); //busca por id si no existe muestra 404

        if ($usuario->id === $request->user()->id) {
            return response()->json(['message' => 'No puedes bloquear tu propia cuenta.'], 422);
        }

        if ($usuario->rol === RolUsuario::Administrador) {
            return response()->json(['message' => 'No se puede bloquear a otro administrador.'], 422);
        }

        $usuario->activo = $request->boolean('activo');
        $usuario->save();

        if (! $usuario->activo) {
            $usuario->tokens()->delete(); //cierra las sesiones abiertas de la cuenta al instante, se eliminan todos los tokens
        }

        //queda registrado quién hizo el cambio (el administrador) y a quién se lo hizo
        $bitacora->registrar(
            $usuario->activo ? EventoBitacora::UsuarioDesbloqueado : EventoBitacora::UsuarioBloqueado,
            $request,
            $request->user(),
            detalles: ['id_usuario_afectado' => $usuario->id],
        );

        return response()->json([
            'message' => $usuario->activo ? 'Usuario desbloqueado.' : 'Usuario bloqueado.',
            'usuario' => $usuario->load('vendedor'),
        ]);
    }
}
