<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Calificacion extends Model
{
    protected $table = 'calificaciones';

    protected $fillable = [
        'id_producto',
        'id_cliente',
        'id_pedido',
        'puntuacion',
        'comentario',
    ];

    protected function casts(): array
    {
        return [
            'puntuacion' => 'integer',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_cliente');
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'id_pedido');
    }
}
