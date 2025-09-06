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

    'client_id' => env('INSTAGRAM_CLIENT_ID'),

    'client_secret' => env('INSTAGRAM_CLIENT_SECRET'),

    'redirect_uri' => env('INSTAGRAM_REDIRECT_URI'),

    'api_base_url' => env('INSTAGRAM_API_BASE_URL', 'https://graph.instagram.com'),

    'api_version' => env('INSTAGRAM_API_VERSION', 'v23.0'),

    'timeout' => env('INSTAGRAM_API_TIMEOUT', 30),

    'retry_attempts' => env('INSTAGRAM_API_RETRY_ATTEMPTS', 3),

    'webhook_verify_token' => env('INSTAGRAM_WEBHOOK_VERIFY_TOKEN', 'default_token'),

];