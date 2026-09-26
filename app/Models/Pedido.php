<?php

namespace App\Models;

use App\Enums\EstadoPedido;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    protected $table = 'pedidos';

    protected $fillable = [
        'folio',
        'id_cliente',
        'id_vendedor',
        'id_direccion',
        'direccion_envio',
        'total',
        'estado',
        'pagado_en',
    ];

    protected function casts(): array
    {
        return [
            'direccion_envio' => 'array',
            'total' => 'decimal:2',
            'estado' => EstadoPedido::class,
            'pagado_en' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_cliente');
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Vendedor::class, 'id_vendedor');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(PedidoDetalle::class, 'id_pedido');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'id_pedido');
    }
}
