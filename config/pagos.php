<?php

return [

    /*
    | Pasarela activa. El resto del sistema solo conoce la interfaz
    | App\Services\Pagos\PasarelaPago, asi cambiar de proveedor es
    | agregar un adaptador nuevo y registrarlo en AppServiceProvider.
    |
    | Soportadas: "simulada"
    */
    'pasarela' => env('PASARELA_PAGO', 'simulada'),

    'simulada' => [
        // Cualquier cobro con total mayor o igual a este monto se rechaza.
        'limite_rechazo' => (float) env('PASARELA_SIMULADA_LIMITE_RECHAZO', 50000),
    ],

];
