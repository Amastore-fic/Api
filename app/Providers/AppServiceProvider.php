<?php

namespace App\Providers;

use App\Services\Pagos\PasarelaPago;
use App\Services\Pagos\PasarelaSimulada;
use Illuminate\Support\ServiceProvider;

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
        //
    }
}
