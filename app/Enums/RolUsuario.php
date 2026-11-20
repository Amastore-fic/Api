<?php

namespace App\Enums;

enum RolUsuario: string
{
    case Cliente = 'cliente';
    case Vendedor = 'vendedor';
    case Administrador = 'administrador';
    // Todo lo del administrador, y ademas puede bloquear a los administradores.
    // No se registra: se asigna con "php artisan usuarios:superadmin {correo}".
    case Superadmin = 'superadmin';
}
