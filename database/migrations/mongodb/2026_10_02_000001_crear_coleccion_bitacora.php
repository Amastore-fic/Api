<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Coleccion "bitacora" en MongoDB: eventos de autenticacion (HU-05).
//
// Esta carpeta (database/migrations/mongodb) la carga AppServiceProvider fuera del
// entorno de pruebas, asi que corre con el "php artisan migrate" de siempre.
// Es idempotente: se puede volver a ejecutar (por ejemplo con migrate:fresh) sin error.
return new class extends Migration
{
    protected $connection = 'mongodb';

    // MongoDB sin replica set no admite transacciones
    public $withinTransaction = false;

    private const COLECCION = 'bitacora';

    public function up(): void
    {
        $base = DB::connection('mongodb')->getDatabase();

        $existe = iterator_to_array($base->listCollectionNames(['filter' => ['name' => self::COLECCION]]));

        if ($existe === []) {
            // Validacion de estructura: MongoDB rechaza documentos sin evento o sin fecha
            $base->createCollection(self::COLECCION, [
                'validator' => ['$jsonSchema' => [
                    'bsonType' => 'object',
                    'required' => ['evento', 'fecha'],
                    'properties' => [
                        'evento' => ['bsonType' => 'string', 'description' => 'Tipo de evento (App\Enums\EventoBitacora)'],
                        'fecha' => ['bsonType' => 'date', 'description' => 'Momento del evento'],
                        'id_usuario' => ['bsonType' => ['int', 'long'], 'description' => 'usuarios.id en PostgreSQL'],
                        'rol' => ['bsonType' => 'string'],
                        'correo_hash' => ['bsonType' => 'string', 'description' => 'SHA-256 del correo, nunca el correo en claro'],
                        'ip' => ['bsonType' => 'string'],
                        'dispositivo' => ['bsonType' => 'string'],
                        'agente' => ['bsonType' => 'string'],
                        'detalles' => ['bsonType' => 'object'],
                    ],
                ]],
                'validationAction' => 'error',
            ]);
        }

        $diasRetencion = max(1, (int) config('bitacora.dias_retencion'));

        // createIndexes no falla si los indices ya existen con la misma definicion
        $base->selectCollection(self::COLECCION)->createIndexes([
            // TTL: MongoDB borra solo los registros con mas de N dias
            ['key' => ['fecha' => 1], 'name' => 'fecha_ttl', 'expireAfterSeconds' => $diasRetencion * 86400],
            // Historial de un usuario, del mas reciente al mas viejo
            ['key' => ['id_usuario' => 1, 'fecha' => -1], 'name' => 'usuario_fecha'],
            // Todos los eventos de un tipo (por ejemplo, intentos fallidos del dia)
            ['key' => ['evento' => 1, 'fecha' => -1], 'name' => 'evento_fecha'],
            // Actividad desde una misma IP (deteccion de ataques de fuerza bruta)
            ['key' => ['ip' => 1, 'fecha' => -1], 'name' => 'ip_fecha'],
        ]);
    }

    public function down(): void
    {
        DB::connection('mongodb')->getDatabase()->dropCollection(self::COLECCION);
    }
};
