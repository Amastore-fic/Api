<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // TODO: usuarios y vendedores, filtrables por rol y paginados
        return $this->pendiente('listar usuarios');
    }

    // PATCH /admin/usuarios/{id}/estado { activo: bool }
    public function cambiarEstado(Request $request, int $id): JsonResponse
    {
        // TODO: bloquear/desbloquear; al bloquear, revocar sus tokens ($usuario->tokens()->delete())
        return $this->pendiente('bloquear o desbloquear cuenta');
    }
}
