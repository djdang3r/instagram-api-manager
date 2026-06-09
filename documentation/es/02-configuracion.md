[◄◄ Instalación](01-instalacion.md)
[▲ Tabla de contenido](00-tabla-de-contenido.md)
[Gestión de Cuentas ►►](03-cuentas.md)

# 🧩 Configuración de API

Para que el paquete funcione correctamente, debes configurar las credenciales de Meta en tu archivo `.env`.

---

### 1. Variables de Entorno

Añade y adapta las siguientes variables a tu archivo `.env`:

```env
# Instagram API — Credenciales OAuth
INSTAGRAM_CLIENT_ID=tu_instagram_client_id
INSTAGRAM_CLIENT_SECRET=tu_instagram_client_secret
INSTAGRAM_REDIRECT_URI=https://tu-dominio.com/instagram/callback
INSTAGRAM_API_BASE_URL=https://graph.instagram.com
INSTAGRAM_API_VERSION=v23.0
INSTAGRAM_API_TIMEOUT=30
INSTAGRAM_API_RETRY_ATTEMPTS=3

# Instagram API — Webhook
INSTAGRAM_WEBHOOK_VERIFY_TOKEN=tu_token_seguro_personalizado
INSTAGRAM_WEBHOOK_PROCESSOR=\ScriptDevelop\InstagramApiManager\Services\WebhookProcessors\BaseWebhookProcessor
INSTAGRAM_OAUTH_BASE_URL=https://api.instagram.com

# Instagram API — Broadcast (Laravel Reverb) 🆕 v1.0.82
INSTAGRAM_BROADCAST_CHANNEL_TYPE=public
INSTAGRAM_CUSTOM_CHANNELS=false

# Facebook API (Requerido para Messenger/Pages/OAuth avanzado)
FACEBOOK_CLIENT_ID=tu_facebook_client_id
FACEBOOK_CLIENT_SECRET=tu_facebook_client_secret
FACEBOOK_REDIRECT_URI=https://tu-dominio.com/facebook/callback
FACEBOOK_API_BASE_URL=https://graph.facebook.com
FACEBOOK_API_VERSION=v25.0
FACEBOOK_API_TIMEOUT=30
FACEBOOK_API_RETRY_ATTEMPTS=3

# Facebook OAuth — Custom Redirect URLs (v1.1.0+)
FACEBOOK_CUSTOM_REDIRECT_SUCCESS_URL=https://tu-dominio.com/oauth/success
FACEBOOK_CUSTOM_REDIRECT_ERROR_URL=https://tu-dominio.com/oauth/error
FACEBOOK_CUSTOM_REDIRECT_WARNING_URL=https://tu-dominio.com/oauth/warning

# Facebook Webhook
FACEBOOK_WEBHOOK_VERIFY_TOKEN=tu_facebook_webhook_token
FACEBOOK_WEBHOOK_RATE_LIMIT_MAX_ATTEMPTS=60
FACEBOOK_WEBHOOK_RATE_LIMIT_DECAY_MINUTES=1

# Facebook Messenger Broadcast (v1.0.82+)
FACEBOOK_BROADCAST_CHANNEL_TYPE=public
FACEBOOK_BROADCAST_DELIVERY_PER_MESSAGE=false

# Facebook Media
FACEBOOK_MEDIA_DISK=public
FACEBOOK_MEDIA_PATH=facebook
FACEBOOK_MME_BASE_URL=https://m.me
FACEBOOK_DOWNLOAD_USER_PROFILE_PICTURE=false

# Instagram OAuth — Custom Redirect URLs (v1.1.0+, mirror)
INSTAGRAM_CUSTOM_REDIRECT_SUCCESS_URL=https://tu-dominio.com/oauth/success
INSTAGRAM_CUSTOM_REDIRECT_ERROR_URL=https://tu-dominio.com/oauth/error
INSTAGRAM_CUSTOM_REDIRECT_WARNING_URL=https://tu-dominio.com/oauth/warning

# Logging (v1.0.83+)
INSTAGRAM_LOGGING_ENABLED=true
FACEBOOK_LOGGING_ENABLED=true
```

| Variable | Descripción | Default |
|---|---|---|
| `INSTAGRAM_BROADCAST_CHANNEL_TYPE` 🆕 | Tipo de canal: `public` o `private` | `public` |
| `INSTAGRAM_CUSTOM_CHANNELS` 🆕 | Si `true`, usa tus propias rutas de canal | `false` |
| `INSTAGRAM_WEBHOOK_PROCESSOR` 🆕 | FQCN del procesador de webhook | `BaseWebhookProcessor` |
| `INSTAGRAM_OAUTH_BASE_URL` | URL base para OAuth de Instagram | `https://api.instagram.com` |

---

### 2. Requisitos para Eventos en Tiempo Real (Laravel Reverb) 🆕

Si planeas usar los eventos broadcast (disponibles desde v1.0.82), asegúrate de tener Laravel Reverb configurado en tu proyecto:

```bash
php artisan reverb:install
```

Y las variables de Reverb en tu `.env`:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=tu_app_id
REVERB_APP_KEY=tu_app_key
REVERB_APP_SECRET=tu_app_secret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

> **Nota:** Los eventos funcionan incluso sin Reverb configurado — simplemente no se transmitirán por WebSocket. Los listeners de backend (síncronos) seguirán funcionando normalmente.

---

### 3. Configuración de Logging (Opcional pero Recomendado)

El paquete incluye un canal de log personalizado para Instagram. Para activarlo, añade el siguiente canal a tu archivo `config/logging.php`:

```php
'channels' => [
    // ... otros canales
    
    'instagram' => [
        'driver' => 'daily',
        'path' => storage_path('logs/instagram.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
        'tap' => [\ScriptDevelop\InstagramApiManager\Logging\CustomizeFormatter::class],
    ],
    
    'facebook' => [
        'driver' => 'daily',
        'path' => storage_path('logs/facebook.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
        'tap' => [\ScriptDevelop\InstagramApiManager\Logging\CustomizeFormatter::class],
    ],
],
```

---

### 4. Archivo de Configuración

Si publicaste la configuración, verás un archivo en `config/instagram.php`. Aquí puedes ajustar los valores por defecto y los modelos que el paquete utilizará para resolver las entidades de Instagram.

```php
return [
    'models' => [
        'business_account'   => \ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount::class,
        'profile'            => \ScriptDevelop\InstagramApiManager\Models\InstagramProfile::class,
        'conversation'       => \ScriptDevelop\InstagramApiManager\Models\InstagramConversation::class,
        'message'            => \ScriptDevelop\InstagramApiManager\Models\InstagramMessage::class,
        'contact'            => \ScriptDevelop\InstagramApiManager\Models\InstagramContact::class,
        'oauth_state'        => \ScriptDevelop\InstagramApiManager\Models\OauthState::class,
    ],

    // 🆕 Sección agregada en v1.0.82
    'broadcast' => [
        'channel_type'    => env('INSTAGRAM_BROADCAST_CHANNEL_TYPE', 'public'),
        'custom_channels' => env('INSTAGRAM_CUSTOM_CHANNELS', false),
    ],

    // 🆕 Sección agregada en v1.0.82
    'events' => [
        'message'      => \ScriptDevelop\InstagramApiManager\Events\InstagramMessageReceived::class,
        'postback'     => \ScriptDevelop\InstagramApiManager\Events\InstagramPostbackReceived::class,
        'reaction'     => \ScriptDevelop\InstagramApiManager\Events\InstagramReactionReceived::class,
        'optin'        => \ScriptDevelop\InstagramApiManager\Events\InstagramOptinReceived::class,
        'referral'     => \ScriptDevelop\InstagramApiManager\Events\InstagramReferralReceived::class,
        'read'         => \ScriptDevelop\InstagramApiManager\Events\InstagramReadReceived::class,
        'message_edit' => \ScriptDevelop\InstagramApiManager\Events\InstagramMessageEdited::class,
    ],

    // 🆕 Campo agregado en v1.0.82
    'webhook' => [
        'verify_token' => env('INSTAGRAM_WEBHOOK_VERIFY_TOKEN', 'default_token'),
        'processor'    => env('INSTAGRAM_WEBHOOK_PROCESSOR', \ScriptDevelop\InstagramApiManager\Services\WebhookProcessors\BaseWebhookProcessor::class),
    ],

    // ... resto de la configuración
];
```

> **Nota:** Puedes sobrescribir las clases de eventos por las tuyas propias para modificar el canal, nombre del evento, o payload transmitido. Solo deben implementar `Illuminate\Contracts\Broadcasting\ShouldBroadcast`.

---

[◄◄ Instalación](01-instalacion.md) | [Gestión de Cuentas ►►](03-cuentas.md)
