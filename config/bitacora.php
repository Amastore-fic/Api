<?php

return [

    // Dias que se conservan los registros. MongoDB borra solo los mas viejos
    // (indice TTL sobre "fecha", ver database/migrations/mongodb).
    'dias_retencion' => (int) env('BITACORA_DIAS_RETENCION', 180),

];
