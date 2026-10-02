<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\UTCDateTime;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Comprueba que la app llega a MongoDB con su usuario y que puede escribir.
//   docker compose exec app php artisan mongodb:estado
Artisan::command('mongodb:estado', function () {
    try {
        $base = DB::connection('mongodb')->getDatabase();

        $base->command(['ping' => 1]);
        $version = $base->command(['buildInfo' => 1])->toArray()[0]['version'];

        // Escritura de prueba: se inserta y se borra un documento
        $prueba = $base->selectCollection('_verificacion');
        $id = $prueba->insertOne(['fecha' => new UTCDateTime])->getInsertedId();
        $prueba->deleteOne(['_id' => $id]);
        $prueba->drop();

        $colecciones = collect(iterator_to_array($base->listCollectionNames()))->sort()->values();

        $this->info('MongoDB responde correctamente.');
        $this->table(['Dato', 'Valor'], [
            ['Versión del servidor', $version],
            ['Base de datos', $base->getDatabaseName()],
            ['Lectura y escritura', 'OK'],
            ['Colecciones', $colecciones->isEmpty() ? '(ninguna todavía)' : $colecciones->implode(', ')],
        ]);

        return 0;
    } catch (Throwable $e) {
        $this->error('No se pudo usar MongoDB: '.$e->getMessage());
        $this->line('Revisa que el contenedor "mongodb" esté encendido (docker compose ps) y las variables MONGO_* de tu .env.');

        return 1;
    }
})->purpose('Verifica la conexión con MongoDB');
