<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrito extends Model
{
    protected $table = 'carritos';

    protected $fillable = [
        'id_usuario',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CarritoItem::class, 'id_carrito');
    }

    // El total siempre lo calcula el servidor; los clientes solo lo muestran.
    public function total(): string
    {
        $total = $this->items->sum(fn (CarritoItem $item) => $item->cantidad * $item->producto->precio);

        return number_format($total, 2, '.', '');
    }
}
