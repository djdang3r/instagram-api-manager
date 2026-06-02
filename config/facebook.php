<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Facebook API Configuration
    |--------------------------------------------------------------------------
    */
    'api' => [
        'base_url' => env('FACEBOOK_API_BASE_URL', 'https://graph.facebook.com'),
        'version' => env('FACEBOOK_API_VERSION', 'v25.0'),
        'timeout' => env('FACEBOOK_API_TIMEOUT', 30),
        'retry_attempts' => env('FACEBOOK_API_RETRY_ATTEMPTS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración del Webhook
    |--------------------------------------------------------------------------
    |
    | Configuración para el webhook de Facebook. Incluye el token de verificación
    | que se utiliza para validar las solicitudes entrantes desde Meta.
    |
    */
    'webhook' => [
        'verify_token' => env('FACEBOOK_WEBHOOK_VERIFY_TOKEN', 'default_token'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Archivos Multimedia
    |--------------------------------------------------------------------------
    |
    | Configuración para el almacenamiento de archivos multimedia de Messenger.
    | Los archivos se guardan en storage/app/public/{path}/ siguiendo el mismo
    | patrón del paquete WhatsApp.
    |
    */
    'media' => [
        'disk' => env('FACEBOOK_MEDIA_DISK', 'public'),
        'base_path' => env('FACEBOOK_MEDIA_PATH', 'facebook'),

        /*
        |----------------------------------------------------------------------
        | Rutas de almacenamiento por tipo de archivo.
        | Extensible: agregá nuevas claves para futuros tipos (stories, etc.)
        |----------------------------------------------------------------------
        */
        'storage_path' => [
            'images' => storage_path('app/public/facebook/images'),
            'audios' => storage_path('app/public/facebook/audios'),
            'videos' => storage_path('app/public/facebook/videos'),
            'documents' => storage_path('app/public/facebook/documents'),
        ],

        /*
        |----------------------------------------------------------------------
        | Tamaños máximos por tipo de archivo (en bytes).
        | Basado en límites oficiales de Meta para Messenger (Mayo 2026).
        | Extensible: agregá nuevas claves para nuevos tipos de media.
        |----------------------------------------------------------------------
        */
        'max_file_size' => [
            'image' => 8 * 1024 * 1024,    // 8 MB (vía URL)
            'audio' => 25 * 1024 * 1024,   // 25 MB
            'video' => 25 * 1024 * 1024,   // 25 MB
            'file' => 25 * 1024 * 1024,    // 25 MB
        ],

        /*
        |----------------------------------------------------------------------
        | Tipos MIME permitidos por tipo de archivo.
        | Basado en documentación oficial de Meta.
        | Extensible: agregá nuevas claves y MIME types según necesidad.
        |----------------------------------------------------------------------
        */
        'allowed_types' => [
            'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'audio' => ['audio/mpeg', 'audio/mp4', 'audio/aac', 'audio/ogg', 'audio/wav'],
            'video' => ['video/mp4', 'video/ogg', 'video/avi', 'video/mov', 'video/webm'],
            'file' => [
                'text/plain', 'application/pdf',
                'application/msword', 'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip',
            ],
        ],

        'download_user_profile_picture' => env('FACEBOOK_DOWNLOAD_USER_PROFILE_PICTURE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Broadcast (Laravel Reverb)
    |--------------------------------------------------------------------------
    |
    | Configuración para broadcasting de eventos Messenger en tiempo real.
    | Independiente de la configuración de Instagram.
    |
    */
    'broadcast' => [
        'channel_type' => env('FACEBOOK_BROADCAST_CHANNEL_TYPE', 'public'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Eventos Personalizados
    |--------------------------------------------------------------------------
    |
    | Clases de eventos broadcast para Messenger. Configurable igual que Instagram.
    |
    */
    'events' => [
        'message' => \ScriptDevelop\InstagramApiManager\Events\MessengerMessageReceived::class,
        'message_echo' => \ScriptDevelop\InstagramApiManager\Events\MessengerMessageEchoReceived::class,
        'postback' => \ScriptDevelop\InstagramApiManager\Events\MessengerPostbackReceived::class,
        'reaction' => \ScriptDevelop\InstagramApiManager\Events\MessengerReactionReceived::class,
        'optin' => \ScriptDevelop\InstagramApiManager\Events\MessengerOptinReceived::class,
        'referral' => \ScriptDevelop\InstagramApiManager\Events\MessengerReferralReceived::class,
        'read' => \ScriptDevelop\InstagramApiManager\Events\MessengerReadReceived::class,
        'message_edit' => \ScriptDevelop\InstagramApiManager\Events\MessengerMessageEdited::class,
        'message_delivered' => \ScriptDevelop\InstagramApiManager\Events\MessengerMessageDelivered::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Meta OAuth
    |--------------------------------------------------------------------------
    |
    | Credenciales y parámetros para la autenticación con Meta/Facebook.
    |
    */
    'meta_auth' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect_uri' => env('FACEBOOK_REDIRECT_URI'),
        'custom_redirect_success_url' => env('FACEBOOK_CUSTOM_REDIRECT_SUCCESS_URL', null),
        'custom_redirect_error_url' => env('FACEBOOK_CUSTOM_REDIRECT_ERROR_URL', null),
        'custom_redirect_warning_url' => env('FACEBOOK_CUSTOM_REDIRECT_WARNING_URL', null),
    ],

];