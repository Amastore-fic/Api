<?php

namespace App\Enums;

enum EstadoProducto: string
{
    case Activo = 'activo';       // visible en el catalogo
    case Retirado = 'retirado';   // retirado por el vendedor o por el administrador
}
