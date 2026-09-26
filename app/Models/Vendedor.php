<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendedor extends Model
{
    protected $table = 'vendedores';

    protected $fillable = [
        'id_usuario',
        'nombre_comercial',
        'codigo_vendedor',
        'sucursal',
        'descripcion',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'id_vendedor');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'id_vendedor');
    }
}
