<?php

/**
 * Canales de logging para Instagram API Manager.
 * 
 * Publicá este archivo en tu proyecto con:
 *   php artisan vendor:publish --tag=instagram-logging
 * 
 * Luego copiá los canales que necesites a tu config/logging.php
 * en la sección 'channels'.
 * 
 * Cada canal usa un "stack" driver que permite prender/apagar
 * los logs desde el archivo .env sin tocar código.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Instagram — Canal de logs
    |--------------------------------------------------------------------------
    |
    | Para activar/desactivar logs de Instagram:
    |   INSTAGRAM_LOGGING_ENABLED=true   → escribe en storage/logs/instagram.log
    |   INSTAGRAM_LOGGING_ENABLED=false  → descarta todos los logs
    |
    */
    'instagram_daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/instagram.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
        'tap' => [\ScriptDevelop\InstagramApiManager\Logging\CustomizeFormatter::class],
    ],

    'instagram' => [
        'driver' => 'stack',
        'channels' => env('INSTAGRAM_LOGGING_ENABLED', true)
            ? ['instagram_daily']
            : ['null'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Facebook Messenger — Canal de logs
    |--------------------------------------------------------------------------
    |
    | Para activar/desactivar logs de Messenger:
    |   FACEBOOK_LOGGING_ENABLED=true   → escribe en storage/logs/facebook.log
    |   FACEBOOK_LOGGING_ENABLED=false  → descarta todos los logs
    |
    */
    'facebook_daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/facebook.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
        'tap' => [\ScriptDevelop\InstagramApiManager\Logging\CustomizeFormatter::class],
    ],

    'facebook' => [
        'driver' => 'stack',
        'channels' => env('FACEBOOK_LOGGING_ENABLED', true)
            ? ['facebook_daily']
            : ['null'],
    ],

];
