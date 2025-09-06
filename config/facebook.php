<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Facebook API Configuration
    |--------------------------------------------------------------------------
    |
    | Define aquí las claves API, secretos, URIs, y otras opciones relevantes
    | para la integración con Graph API de Facebook y Messenger.
    |
    */

    'client_id' => env('FACEBOOK_CLIENT_ID'),

    'client_secret' => env('FACEBOOK_CLIENT_SECRET'),

    'redirect_uri' => env('FACEBOOK_REDIRECT_URI'),

    'api_base_url' => env('FACEBOOK_API_BASE_URL', 'https://graph.facebook.com'),

    'api_version' => env('FACEBOOK_API_VERSION', 'v23.0'),

    'timeout' => env('FACEBOOK_API_TIMEOUT', 30),

    'retry_attempts' => env('FACEBOOK_API_RETRY_ATTEMPTS', 3),

    'webhook_verify_token' => env('FACEBOOK_WEBHOOK_VERIFY_TOKEN', 'default_token'),


];
