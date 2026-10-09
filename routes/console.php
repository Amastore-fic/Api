<?php

use App\Models\EventoBitacora;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
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

// Comprueba que la app llega a Redis y que la cache lo esta usando.
//   docker compose exec app php artisan redis:estado
Artisan::command('redis:estado', function () {
    try {
        $redis = Redis::connection('cache');
        $redis->ping();
        $info = $redis->info('server');
        $version = $info['redis_version'] ?? $info['Server']['redis_version'] ?? '?';

        // Escritura de prueba por la cache de Laravel: se guarda, se lee y se borra
        $clave = '_verificacion_'.Str::random(8);
        Cache::store('redis')->put($clave, 'ok', 10);
        $leido = Cache::store('redis')->get($clave);
        Cache::store('redis')->forget($clave);

        if ($leido !== 'ok') {
            throw new RuntimeException('se escribió una clave, pero no se pudo leer de vuelta.');
        }

        $this->info('Redis responde correctamente.');
        $this->table(['Dato', 'Valor'], [
            ['Versión del servidor', $version],
            ['Lectura y escritura', 'OK'],
            ['Cache de la app (CACHE_STORE)', config('cache.default')],
        ]);

        if (config('cache.default') === 'database') {
            $this->warn('La app sigue usando la cache de PostgreSQL. Revisa CACHE_STORE en docker-compose.yml y ejecuta "docker compose up -d".');
        }

        return 0;
    } catch (Throwable $e) {
        $this->error('No se pudo usar Redis: '.$e->getMessage());
        $this->line('Revisa que el contenedor "redis" esté encendido (docker compose ps). Mientras tanto, la cache usa PostgreSQL.');

        return 1;
    }
})->purpose('Verifica la conexión con Redis');

// Muestra los registros mas recientes de la bitacora (MongoDB).
//   docker compose exec app php artisan bitacora:ver
//   docker compose exec app php artisan bitacora:ver --evento=login_fallido --limite=20
Artisan::command('bitacora:ver {--limite=10 : Cuantos registros mostrar} {--evento= : Filtrar por tipo de evento} {--usuario= : Filtrar por id de usuario}', function () {
    try {
        $consulta = EventoBitacora::query()->orderBy('fecha', 'desc')->limit(max(1, (int) $this->option('limite')));

        if ($evento = $this->option('evento')) {
            $consulta->where('evento', $evento);
        }
        if ($usuario = $this->option('usuario')) {
            $consulta->where('id_usuario', (int) $usuario);
        }

        $registros = $consulta->get();

        $this->info('Bitácora: '.EventoBitacora::count().' registros en total.');

        if ($registros->isEmpty()) {
            $this->line('No hay registros con esos filtros. Inicia sesión en la tienda o el panel y vuelve a consultar.');

            return 0;
        }

        $this->table(
            ['Fecha (UTC)', 'Evento', 'Usuario', 'Rol', 'IP', 'Dispositivo', 'Detalles'],
            $registros->map(fn (EventoBitacora $r) => [
                $r->fecha?->utc()->format('Y-m-d H:i:s'),
                $r->evento,
                $r->id_usuario ?? '-',
                $r->rol ?? '-',
                $r->ip ?? '-',
                $r->dispositivo ?? '-',
                $r->detalles ? json_encode($r->detalles, JSON_UNESCAPED_UNICODE) : '-',
            ])
        );

        return 0;
    } catch (Throwable $e) {
        $this->error('No se pudo leer la bitácora: '.$e->getMessage());

        return 1;
    }
})->purpose('Muestra los registros más recientes de la bitácora (MongoDB)');
