<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Modelos Personalizados
    |--------------------------------------------------------------------------
    |
    | Aquí puedes especificar los modelos que el paquete utilizará para las
    | entidades principales. Puedes sobrescribir estos valores en tu archivo
    | .env si estás utilizando modelos personalizados.
    |
    */
    'models' => [
        'facebook_page' => \ScriptDevelop\InstagramApiManager\Models\FacebookPage::class,
        'facebook_page_stats' => \ScriptDevelop\InstagramApiManager\Models\FacebookPageStats::class,
        'facebook_post' => \ScriptDevelop\InstagramApiManager\Models\FacebookPost::class,
        'facebook_comment' => \ScriptDevelop\InstagramApiManager\Models\FacebookComment::class,
        'facebook_media' => \ScriptDevelop\InstagramApiManager\Models\FacebookMedia::class,

        'instagram_business_account' => \ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount::class,
        'instagram_account_stats' => \ScriptDevelop\InstagramApiManager\Models\InstagramAccountStats::class,

        'instagram_profile' => \ScriptDevelop\InstagramApiManager\Models\InstagramProfile::class,
        'instagram_contact' => \ScriptDevelop\InstagramApiManager\Models\InstagramContact::class,
        'instagram_conversation' => \ScriptDevelop\InstagramApiManager\Models\InstagramConversation::class,

        //Mensajes
        'instagram_message' => \ScriptDevelop\InstagramApiManager\Models\InstagramMessage::class,
        'instagram_media_message' => \ScriptDevelop\InstagramApiManager\Models\InstagramMediaMessage::class,
        'instagram_referral' => \ScriptDevelop\InstagramApiManager\Models\InstagramReferral::class,

        // Comentarios y contenido
        'instagram_comment' => \ScriptDevelop\InstagramApiManager\Models\InstagramComment::class,
        'instagram_post' => \ScriptDevelop\InstagramApiManager\Models\InstagramPost::class,
        'instagram_story' => \ScriptDevelop\InstagramApiManager\Models\InstagramStory::class,
        'instagram_media_post' => \ScriptDevelop\InstagramApiManager\Models\InstagramMediaPost::class,
        'instagram_media_stats' => \ScriptDevelop\InstagramApiManager\Models\InstagramMediaStats::class,

        // Messenger
        'messenger_contact' => \ScriptDevelop\InstagramApiManager\Models\MessengerContact::class,
        'messenger_conversation' => \ScriptDevelop\InstagramApiManager\Models\MessengerConversation::class,
        'messenger_insights' => \ScriptDevelop\InstagramApiManager\Models\MessengerInsights::class,

        //Mensajes messenger
        'messenger_message' => \ScriptDevelop\InstagramApiManager\Models\MessengerMessage::class,
        'messenger_media_message' => \ScriptDevelop\InstagramApiManager\Models\MessengerMediaMessage::class,
        'messenger_referral' => \ScriptDevelop\InstagramApiManager\Models\MessengerReferral::class,

        'meta_app' => \ScriptDevelop\InstagramApiManager\Models\MetaApp::class,

        // Modelo para autenticacion Oauth
        'oauth_state' => \ScriptDevelop\InstagramApiManager\Models\OauthState::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Instagram API Configuration
    |--------------------------------------------------------------------------
    */
    'api' => [
        // URLs base para diferentes tipos de endpoints
        'oauth_base_url' => env('INSTAGRAM_OAUTH_BASE_URL', 'https://api.instagram.com'),
        'graph_base_url' => env('INSTAGRAM_GRAPH_BASE_URL', 'https://graph.instagram.com'),

        'version' => env('INSTAGRAM_API_VERSION', 'v25.0'),
        'timeout' => env('INSTAGRAM_API_TIMEOUT', 30),
        'retry_attempts' => env('INSTAGRAM_API_RETRY_ATTEMPTS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración del Webhook
    |--------------------------------------------------------------------------
    |
    | Configuración para el webhook de Instagram. Incluye el token de verificación
    | que se utiliza para validar las solicitudes entrantes desde Meta.
    |
    */
    'webhook' => [
        'verify_token' => env('INSTAGRAM_WEBHOOK_VERIFY_TOKEN', 'default_token'),
        'processor' => env('INSTAGRAM_WEBHOOK_PROCESSOR', \ScriptDevelop\InstagramApiManager\Services\WebhookProcessors\BaseWebhookProcessor::class),
        'async' => env('INSTAGRAM_WEBHOOK_ASYNC', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Controla el límite de peticiones al webhook de Instagram.
    | GET (verificación): 10 peticiones/min. POST (procesamiento): configurable.
    |
    */
    'rate_limit' => [
        'max_attempts' => env('INSTAGRAM_RATE_LIMIT', 60),
        'decay_minutes' => env('INSTAGRAM_RATE_LIMIT_DECAY', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Archivos Multimedia
    |--------------------------------------------------------------------------
    |
    | Configuración para el almacenamiento de archivos multimedia de Instagram.
    | Los archivos se guardan en storage/app/public/{path}/ siguiendo el mismo
    | patrón del paquete WhatsApp.
    |
    */
    'media' => [
        'disk' => env('INSTAGRAM_MEDIA_DISK', 'public'),
        'base_path' => env('INSTAGRAM_MEDIA_PATH', 'instagram'),

        /*
        |----------------------------------------------------------------------
        | Rutas de almacenamiento por tipo de archivo.
        | Extensible: agregá nuevas claves para futuros tipos (stories, reels, etc.)
        |----------------------------------------------------------------------
        */
        'storage_path' => [
            'images' => storage_path('app/public/instagram/images'),
            'audios' => storage_path('app/public/instagram/audios'),
            'videos' => storage_path('app/public/instagram/videos'),
            'documents' => storage_path('app/public/instagram/documents'),
        ],

        /*
        |----------------------------------------------------------------------
        | Tamaños máximos por tipo de archivo (en bytes).
        | Basado en límites oficiales de Meta (Mayo 2026).
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
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Broadcast (Laravel Reverb)
    |--------------------------------------------------------------------------
    |
    | Configuración para el sistema de broadcasting de eventos en tiempo real.
    | Permite transmitir eventos de webhooks de Instagram a través de Laravel
    | Reverb, Pusher u otros drivers de broadcasting compatibles.
    |
    */
    'broadcast' => [
        'channel_type' => env('INSTAGRAM_BROADCAST_CHANNEL_TYPE', 'public'), // 'public' o 'private'
        'custom_channels' => env('INSTAGRAM_CUSTOM_CHANNELS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Eventos Personalizados
    |--------------------------------------------------------------------------
    |
    | Aquí puedes especificar los eventos que el paquete utilizará para el
    | broadcasting. Puedes cambiar estas clases por personalizadas.
    |
    */
    'events' => [
        'message' => \ScriptDevelop\InstagramApiManager\Events\InstagramMessageReceived::class,
        'message_echo' => \ScriptDevelop\InstagramApiManager\Events\InstagramMessageEchoReceived::class,
        'postback' => \ScriptDevelop\InstagramApiManager\Events\InstagramPostbackReceived::class,
        'reaction' => \ScriptDevelop\InstagramApiManager\Events\InstagramReactionReceived::class,
        'optin' => \ScriptDevelop\InstagramApiManager\Events\InstagramOptinReceived::class,
        'referral' => \ScriptDevelop\InstagramApiManager\Events\InstagramReferralReceived::class,
        'read' => \ScriptDevelop\InstagramApiManager\Events\InstagramReadReceived::class,
        'message_edit' => \ScriptDevelop\InstagramApiManager\Events\InstagramMessageEdited::class,
        'comment' => \ScriptDevelop\InstagramApiManager\Events\InstagramCommentReceived::class,
        'mention' => \ScriptDevelop\InstagramApiManager\Events\InstagramMentionReceived::class,
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
        'client_id' => env('INSTAGRAM_CLIENT_ID'),
        'client_secret' => env('INSTAGRAM_CLIENT_SECRET'),
        'redirect_uri' => env('INSTAGRAM_REDIRECT_URI'),
        'custom_redirect_success_url' => env('INSTAGRAM_CUSTOM_REDIRECT_SUCCESS_URL', null),
        'custom_redirect_error_url' => env('INSTAGRAM_CUSTOM_REDIRECT_ERROR_URL', null),
        'custom_redirect_warning_url' => env('INSTAGRAM_CUSTOM_REDIRECT_WARNING_URL', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Comentarios
    |--------------------------------------------------------------------------
    */
    'comments' => [
        'enabled' => env('INSTAGRAM_COMMENTS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Publicación de Contenido
    |--------------------------------------------------------------------------
    */
    'publishing' => [
        'max_caption_length' => 2200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Cache
    |--------------------------------------------------------------------------
    |
    | TTL en segundos para cachear perfiles de contacto.
    | 3600 = 1 hora. 0 = deshabilitado (cada mensaje consulta la API).
    |
    */
    'cache' => [
        'contact_profile_enabled' => env('INSTAGRAM_CONTACT_CACHE_ENABLED', true),
        'contact_profile_ttl' => env('INSTAGRAM_CONTACT_CACHE_TTL', 3600),
    ],

];