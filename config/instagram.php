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

        'instagram_business_account' => \ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount::class,

        'instagram_contact' => \ScriptDevelop\InstagramApiManager\Models\InstagramContact::class,

        'instagram_conversation' => \ScriptDevelop\InstagramApiManager\Models\InstagramConversation::class,

        //Mensajes
        'instagram_message' => \ScriptDevelop\InstagramApiManager\Models\InstagramMessage::class,

        'instagram_profile' => \ScriptDevelop\InstagramApiManager\Models\InstagramProfile::class,

        'instagram_referral' => \ScriptDevelop\InstagramApiManager\Models\InstagramReferral::class,

        'messenger_contact' => \ScriptDevelop\InstagramApiManager\Models\MessengerContact::class,

        'messenger_conversation' => \ScriptDevelop\InstagramApiManager\Models\MessengerConversation::class,

        //Mensajes messenger
        'messenger_message' => \ScriptDevelop\InstagramApiManager\Models\MessengerMessage::class,

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

        'version' => env('INSTAGRAM_API_VERSION', 'v19.0'),
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

        // Procesador personalizado para webhooks (valor por defecto)
        'processor' => env('INSTAGRAM_WEBHOOK_PROCESSOR', \ScriptDevelop\InstagramApiManager\Services\WebhookProcessors\BaseWebhookProcessor::class),
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
        'postback' => \ScriptDevelop\InstagramApiManager\Events\InstagramPostbackReceived::class,
        'reaction' => \ScriptDevelop\InstagramApiManager\Events\InstagramReactionReceived::class,
        'optin' => \ScriptDevelop\InstagramApiManager\Events\InstagramOptinReceived::class,
        'referral' => \ScriptDevelop\InstagramApiManager\Events\InstagramReferralReceived::class,
        'read' => \ScriptDevelop\InstagramApiManager\Events\InstagramReadReceived::class,
        'message_edit' => \ScriptDevelop\InstagramApiManager\Events\InstagramMessageEdited::class,
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
    ]
];