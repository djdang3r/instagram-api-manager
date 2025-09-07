<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Facebook API Configuration
    |--------------------------------------------------------------------------
    */

    'client_id' => env('FACEBOOK_CLIENT_ID'),
    'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
    'redirect_uri' => env('FACEBOOK_REDIRECT_URI'),

    'api_base_url' => env('FACEBOOK_API_BASE_URL', 'https://graph.facebook.com'),
    'api_version' => env('FACEBOOK_API_VERSION', 'v19.0'),
    'timeout' => env('FACEBOOK_API_TIMEOUT', 30),
    'retry_attempts' => env('FACEBOOK_API_RETRY_ATTEMPTS', 3),

    'webhook_verify_token' => env('FACEBOOK_WEBHOOK_VERIFY_TOKEN', 'default_token'),
];