<?php

namespace App\Enums;

// En el MVP el ciclo de vida termina en un estado administrativo:
// no hay entrega fisica ni actualizaciones desde una paqueteria.
enum EstadoPedido: string
{
    case PendientePago = 'pendiente_pago';
    case Pagado = 'pagado';
    case EnPreparacion = 'en_preparacion';
    case Enviado = 'enviado';
    case Cancelado = 'cancelado';
}
