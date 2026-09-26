<?php

namespace App\Enums;

enum RolUsuario: string
{
    case Cliente = 'cliente';
    case Vendedor = 'vendedor';
    case Administrador = 'administrador';
}
