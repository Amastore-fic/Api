<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

// Un registro de la bitacora. Vive en MongoDB (coleccion "bitacora"), no en PostgreSQL.
// Se escribe a traves de App\Services\BitacoraService.
class EventoBitacora extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'bitacora';

    // Solo se usa "fecha": un evento nunca se modifica despues de escribirse
    public $timestamps = false;

    protected $fillable = [
        'evento',
        'id_usuario',
        'rol',
        'correo_hash',
        'ip',
        'dispositivo',
        'agente',
        'detalles',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'detalles' => 'array',
        ];
    }
}
