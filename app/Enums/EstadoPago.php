<?php

namespace App\Enums;

enum EstadoPago: string
{
    case Pendiente = 'pendiente';
    case Aprobado = 'aprobado';
    case Rechazado = 'rechazado';
}
