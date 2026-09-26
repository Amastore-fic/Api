<?php

namespace App\Models;

use App\Enums\EstadoPago;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'id_pedido',
        'pasarela',
        'referencia_pasarela',
        'monto',
        'estado',
        'respuesta_pasarela',
    ];

    protected $hidden = [
        'respuesta_pasarela',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'estado' => EstadoPago::class,
            'respuesta_pasarela' => 'array',
        ];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'id_pedido');
    }
}
