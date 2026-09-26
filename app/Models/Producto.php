<?php

namespace App\Models;

use App\Enums\EstadoProducto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'id_vendedor',
        'id_categoria',
        'titulo',
        'descripcion',
        'precio',
        'existencias',
        'imagen_url',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'existencias' => 'integer',
            'estado' => EstadoProducto::class,
        ];
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', EstadoProducto::Activo);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Vendedor::class, 'id_vendedor');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }

    public function calificaciones(): HasMany
    {
        return $this->hasMany(Calificacion::class, 'id_producto');
    }
}
