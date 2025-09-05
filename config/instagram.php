<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Instagram API Configuration
    |--------------------------------------------------------------------------
    |
    | Aquí puedes definir opciones como claves API, secretos, opciones por defecto,
    | tiempos de espera, etc.
    |
    */

    'api_base_url' => env('INSTAGRAM_API_BASE_URL', 'https://graph.instagram.com'),

    'default_account' => env('INSTAGRAM_DEFAULT_ACCOUNT', null),

    'timeout' => env('INSTAGRAM_API_TIMEOUT', 30),

    'retry_attempts' => env('INSTAGRAM_API_RETRY_ATTEMPTS', 3),

];