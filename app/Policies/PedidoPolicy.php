<?php

namespace App\Policies;

use App\Enums\RolUsuario;
use App\Models\Pedido;
use App\Models\Usuario;

class PedidoPolicy
{
    // Cliente: solo sus pedidos. Vendedor: los que le corresponden. Admin: todos.
    public function view(Usuario $usuario, Pedido $pedido): bool
    {
        return match ($usuario->rol) {
            RolUsuario::Administrador => true,
            RolUsuario::Cliente => $pedido->id_cliente === $usuario->id,
            RolUsuario::Vendedor => $pedido->id_vendedor === $usuario->vendedor?->id,
        };
    }

    public function pagar(Usuario $usuario, Pedido $pedido): bool
    {
        return $usuario->rol === RolUsuario::Cliente && $pedido->id_cliente === $usuario->id;
    }
}
