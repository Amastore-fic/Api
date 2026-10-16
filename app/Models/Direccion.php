<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Direccion extends Model
{
    // Los pedidos referencian la direccion por FK: al borrarla solo se oculta
    use SoftDeletes;

    protected $table = 'direcciones';

    protected $fillable = [
        'id_usuario',
        'destinatario',
        'calle',
        'numero_exterior',
        'numero_interior',
        'colonia',
        'ciudad',
        'estado',
        'codigo_postal',
        'telefono',
        'referencias',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}
