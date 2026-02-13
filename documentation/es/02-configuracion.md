[◄◄ Instalación](01-instalacion.md)
[▲ Tabla de contenido](00-tabla-de-contenido.md)
[Gestión de Cuentas ►►](03-cuentas.md)

# 🧩 Configuración de API

Para que el paquete funcione correctamente, debes configurar las credenciales de Meta en tu archivo `.env`.

### 1. Variables de Entorno

Añade y adapta las siguientes variables a tu archivo `.env`:

```env
# Instagram API
INSTAGRAM_CLIENT_ID=tu_instagram_client_id
INSTAGRAM_CLIENT_SECRET=tu_instagram_client_secret
INSTAGRAM_REDIRECT_URI=https://tu-dominio.com/instagram/callback
INSTAGRAM_API_BASE_URL=https://graph.instagram.com
INSTAGRAM_API_VERSION=v23.0
INSTAGRAM_API_TIMEOUT=30
INSTAGRAM_API_RETRY_ATTEMPTS=3
INSTAGRAM_WEBHOOK_VERIFY_TOKEN=tu_token_seguro_personalizado

# Facebook API (Requerido para Messenger/Pages/OAuth avanzado)
FACEBOOK_CLIENT_ID=tu_facebook_client_id
FACEBOOK_CLIENT_SECRET=tu_facebook_client_secret
FACEBOOK_REDIRECT_URI=https://tu-dominio.com/facebook/callback
FACEBOOK_API_BASE_URL=https://graph.facebook.com
FACEBOOK_API_VERSION=v23.0
```

### 2. Configuración de Logging (Opcional pero Recomendado)

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

### 3. Archivo de Configuración

Si publicaste la configuración, verás un archivo en `config/instagram.php`. Aquí puedes ajustar los valores por defecto y los modelos que el paquete utilizará para resolver las entidades de Instagram.

```php
return [
    'models' => [
        'business_account' => \ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount::class,
        'profile' => \ScriptDevelop\InstagramApiManager\Models\InstagramProfile::class,
        'conversation' => \ScriptDevelop\InstagramApiManager\Models\InstagramConversation::class,
        'message' => \ScriptDevelop\InstagramApiManager\Models\InstagramMessage::class,
        'contact' => \ScriptDevelop\InstagramApiManager\Models\InstagramContact::class,
    ],
    // ...
];
```

---
[◄◄ Instalación](01-instalacion.md) | [Gestión de Cuentas ►►](03-cuentas.md)
