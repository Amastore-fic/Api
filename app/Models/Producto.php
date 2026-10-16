<?php

namespace App\Models;

use App\Enums\EstadoProducto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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
            'precio'      => 'decimal:2',
            'existencias' => 'integer',
            'estado'      => EstadoProducto::class,
        ];
    }

    /**
     * Accessor para retornar la URL pública de la imagen.
     */
    protected function imagenUrl(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (!$value) {
                    return null;
                }

                if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                    return $value;
                }

                return Storage::disk('public')->url($value);
            }
        );
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