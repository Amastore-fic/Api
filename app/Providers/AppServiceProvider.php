<?php

namespace App\Providers;

use App\Services\Pagos\PasarelaPago;
use App\Services\Pagos\PasarelaSimulada;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\EloquentUserProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Para agregar una pasarela real: crear otro adaptador que implemente
        // PasarelaPago y agregar su caso aqui.
        $this->app->bind(PasarelaPago::class, fn () => match (config('pagos.pasarela')) {
            'simulada' => new PasarelaSimulada((float) config('pagos.simulada.limite_rechazo')),
        });
    }

    public function boot(): void
    {
        // Migraciones de MongoDB (coleccion de bitacora). Corren con el "php artisan migrate"
        // de siempre, pero no en las pruebas, que no deben depender de MongoDB.
        if (! $this->app->environment('testing')) {
            $this->loadMigrationsFrom(database_path('migrations/mongodb'));
        }

        Auth::provider('usuarios-provider', function ($app, array $config) {
            return new class($app['hash'], $config['model']) extends EloquentUserProvider {
                public function retrieveByCredentials(array $credentials)
                {
                    if (isset($credentials['email'])) {
                        $credentials['correo'] = $credentials['email'];
                        unset($credentials['email']);
                    }
                    return parent::retrieveByCredentials($credentials);
                }
            };
        });
        ResetPassword::createUrlUsing(function ($usuario, string $token) {
            $frontendUrl = rtrim(config('app.frontend_url'), '/');


            return "{$frontendUrl}/restablecer-password?token={$token}&correo=" . urlencode($usuario->correo);
        });
    }
}
