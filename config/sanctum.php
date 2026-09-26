<?php

return [

    /*
    | Los clientes (web y Android) se autentican con token Bearer, no con
    | cookies de sesion, por eso no se configuran dominios "stateful".
    */
    'stateful' => [],

    'guard' => ['web'],

    // Minutos de vida del token emitido en /auth/login (null = no expira).
    'expiration' => env('SANCTUM_EXPIRATION') ? (int) env('SANCTUM_EXPIRATION') : null,

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

];
