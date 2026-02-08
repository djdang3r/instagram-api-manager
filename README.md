Instagram API Manager for Laravel
[![Latest Version](https://img.shields.io/github/release/licenser es un paquete para Laravel diseñado para facilitar la integración y gestión avanzada de cuentas Instagram Business mediante la API Graph y Messenger API oficial de Meta. Este paquete ofrece:

Administración centralizada de cuentas Instagram vinculadas a Facebook Pages.

Manejo automático y seguro de tokens de acceso.

Gestión de mensajes, conversaciones y multimedia en Instagram Direct.

Recepción y manejo de Webhooks para eventos en tiempo real.

Herramientas y convenciones listas para producción en Laravel 12+.

Descripción del paquete
Este paquete provee una solución robusta para desarrolladores y empresas que requieren integrar Instagram Business de forma completa a sus aplicaciones Laravel. Incluye:

Modelos Eloquent optimizados.

Integración con la API oficial mediante un cliente HTTP configurado con logging personalizado.

Soporte completo para Webhooks con verificación automática.

Facades y servicios modulares para un desarrollo limpio y sencillo.

Requisitos
PHP >= 8.1

Laravel >= 12

Composer

Cuenta Facebook con Instagram Business vinculada

Permisos y configuraciones en Meta for Developers para la app y acceso API

Instalación
Instalar el paquete vía Composer:

composer require scriptdevelop/instagram-api-manager

Publicar configuraciones y recursos necesarios (puedes publicar todo junto o por partes):

Publicar todo junto
php artisan vendor:publish --tag=instagram-api-manager

O publicar por partes
php artisan vendor:publish --tag=instagram-migrations
php artisan vendor:publish --tag=instagram-facebook-config
php artisan vendor:publish --tag=instagram-callback-routes
php artisan vendor:publish --tag=instagram-webhook-routes
php artisan vendor:publish --tag=instagram-logging

Ejecutar las migraciones:

php artisan migrate

Añadir la variable de entorno para la verificación del Webhook en tu archivo .env:

INSTAGRAM_WEBHOOK_VERIFY_TOKEN=tu_token_seguro_personalizado

(Opcional) Actualizar el archivo config/instagram.php para personalizar valores por defecto.

En app/Http/Middleware/VerifyCsrfToken.php, agregar la excepción para la ruta del webhook:

protected $except = [
'instagram-webhook',
];

Integrar y adaptar la ruta webhook publicada en routes/instagram_webhook.php para recibir eventos de Instagram.

Uso básico
Configuración de conexión API
Configura en tu .env:

# Instagram OAuth / API
INSTAGRAM_CLIENT_ID=tu_instagram_client_id
INSTAGRAM_CLIENT_SECRET=tu_instagram_client_secret
INSTAGRAM_REDIRECT_URI=https://tu-dominio.com/instagram/callback
INSTAGRAM_API_BASE_URL=https://graph.instagram.com
INSTAGRAM_API_VERSION=v23.0
INSTAGRAM_API_TIMEOUT=30
INSTAGRAM_API_RETRY_ATTEMPTS=3
INSTAGRAM_WEBHOOK_VERIFY_TOKEN=tu_token_seguro_para_webhook_instagram

# Facebook OAuth / API (Messenger, Pages, etc)
FACEBOOK_CLIENT_ID=tu_facebook_client_id
FACEBOOK_CLIENT_SECRET=tu_facebook_client_secret
FACEBOOK_REDIRECT_URI=https://tu-dominio.com/facebook/callback
FACEBOOK_API_BASE_URL=https://graph.facebook.com
FACEBOOK_API_VERSION=v23.0
FACEBOOK_API_TIMEOUT=30
FACEBOOK_API_RETRY_ATTEMPTS=3
FACEBOOK_WEBHOOK_VERIFY_TOKEN=tu_token_seguro_para_webhook_facebook

El paquete utilizará esta configuración automáticamente.

Manejo de cuentas
Obtención y almacenamiento de páginas y cuentas Instagram Business vinculadas usando el endpoint GET_USER_MANAGED_PAGES.

Uso de modelos Eloquent como InstagramBusinessAccount, InstagramProfile, InstagramConversation, InstagramMessage y InstagramContact.

Puedes crear servicios o jobs que sincronicen cuentas y datos usando el cliente ApiClient del paquete.

Envío y recepción de mensajes
Usar el cliente API para enviar mensajes de texto, multimedia y plantillas mediante endpoints como SEND_TEXT_MESSAGE.

Recibir mensajes y eventos en tiempo real a través del webhook en la ruta /instagram-webhook.

Procesar eventos en InstagramWebhookController o delegar la lógica en servicios especializados.

Multimedia y reels
Subir Reels a contenedores con UPLOAD_REEL_CONTAINER.

Consultar estado del contenedor mediante GET_IG_CONTAINER_STATUS.

Publicar Reels usando PUBLISH_REEL.

Subir attachments para mensajes (fotos, GIFs) con UPLOAD_MESSAGE_ATTACHMENT.

Logging personalizado
El paquete utiliza un canal de log Laravel llamado instagram configurado para guardar logs en storage/logs/instagram.log. Para habilitarlo, añade este canal en config/logging.php:

'channels' => [
// Otros canales existentes...

'channels' => [
    // Canales existentes...

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

Publicar y configurar webhook (paso a paso)
El webhook recibe la verificación de Meta para suscribirse automáticamente enviando el token configurado en INSTAGRAM_WEBHOOK_VERIFY_TOKEN.

El webhook recibe eventos POST para manejar mensajes, reacciones y más.

La ruta /instagram-webhook está registrada y excluida de CSRF para evitar bloqueos.

Puedes personalizar el controlador InstagramWebhookController según tus necesidades.


Autenticacion de usuarios:
use ScriptDevelop\InstagramApiManager\Facades\Instagram;
$url = Instagram::account()->getAuthorizationUrl();



use ScriptDevelop\InstagramApiManager\Facades\Instagram;
use ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount;

// Obtener una cuenta
$account = InstagramBusinessAccount::first();

// Usar la cuenta para obtener información
$profile = Instagram::forAccount($account)->getProfileInfo();
$media = Instagram::forAccount($account)->getUserMedia();

// O usando el método helper account()
$profile = Instagram::account($account)->getProfileInfo();
$media = Instagram::account($account)->getUserMedia();

// O usando el ID de la cuenta
$profile = Instagram::account('17918115224312316')->getProfileInfo();
$media = Instagram::account('17918115224312316')->getUserMedia();

// También puedes seguir usando los métodos con parámetros explícitos
$profile = Instagram::account()->getProfileInfo($accessToken);
$media = Instagram::account()->getUserMedia($userId, $accessToken);

// Opción 4: Encadenamiento manual
$service = Instagram::account();
$service->forAccount($account);
$profile = $service->getProfileInfo();










use ScriptDevelop\InstagramApiManager\Facades\Instagram;
use ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount;

// Obtener una cuenta
$account = InstagramBusinessAccount::first();

// Enviar un mensaje de texto
$result = Instagram::message()
    ->withAccessToken($account->access_token)
    ->withInstagramUserId($account->instagram_business_account_id)
    ->sendTextMessage('RECIPIENT_IGSID', 'Hola, este es un mensaje de prueba');

// Enviar una imagen
$result = Instagram::message()
    ->withAccessToken($account->access_token)
    ->withInstagramUserId($account->instagram_business_account_id)
    ->sendImageMessage('RECIPIENT_IGSID', 'https://example.com/image.jpg');

// Enviar un sticker
$result = Instagram::message()
    ->withAccessToken($account->access_token)
    ->withInstagramUserId($account->instagram_business_account_id)
    ->sendStickerMessage('RECIPIENT_IGSID');

// Reaccionar a un mensaje
$result = Instagram::message()
    ->withAccessToken($account->access_token)
    ->withInstagramUserId($account->instagram_business_account_id)
    ->reactToMessage('RECIPIENT_IGSID', 'MESSAGE_ID', 'love');

// Obtener conversaciones
$conversations = Instagram::message()
    ->withAccessToken($account->access_token)
    ->withInstagramUserId($account->instagram_business_account_id)
    ->getConversations();










use ScriptDevelop\InstagramApiManager\Facades\Instagram;

// Obtener una cuenta
$account = InstagramBusinessAccount::first();

// Preparar quick replies
$quickReplies = [
    [
        'content_type' => 'text',
        'title' => 'Opción 1',
        'payload' => 'OPTION_1_PAYLOAD'
    ],
    [
        'content_type' => 'text',
        'title' => 'Opción 2',
        'payload' => 'OPTION_2_PAYLOAD'
    ],
    [
        'content_type' => 'text',
        'title' => 'Opción 3',
        'payload' => 'OPTION_3_PAYLOAD'
    ]
];

// Enviar mensaje con quick replies
$result = Instagram::message()
    ->withAccessToken($account->access_token)
    ->withInstagramUserId($account->instagram_business_account_id)
    ->sendQuickReplies('RECIPIENT_IGSID', 'Por favor selecciona una opción:', $quickReplies);










use ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount;
use ScriptDevelop\InstagramApiManager\Facades\Instagram;

$account = InstagramBusinessAccount::first();

$elements = [
    [
        'title' => 'Welcome!',
        'image_url' => 'https://example.com/image.jpg',
        'subtitle' => 'This is a generic template example',
        'default_action' => [
            'type' => 'web_url',
            'url' => 'https://example.com'
        ],
        'buttons' => [
            [
                'type' => 'web_url',
                'url' => 'https://example.com',
                'title' => 'Visit Website'
            ],
            [
                'type' => 'postback',
                'title' => 'Start Chat',
                'payload' => 'START_CHAT_PAYLOAD'
            ]
        ]
    ]
];

$result = Instagram::message()
    ->withAccessToken($account->access_token)
    ->withInstagramUserId($account->instagram_business_account_id)
    ->sendGenericTemplate('807022408557003', $elements);


use ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount;
use ScriptDevelop\InstagramApiManager\Facades\Instagram;
$account = InstagramBusinessAccount::first();

$elements = [
    [
        'title' => 'Bienvenido a nuestra tienda',
        'image_url' => 'https://example.com/welcome.jpg',
        'subtitle' => '¿Cómo podemos ayudarte hoy?',
        'default_action' => [
            'type' => 'web_url',
            'url' => 'https://example.com'
        ],
        'buttons' => [
            [
                'type' => 'postback',
                'title' => 'Ver Productos',
                'payload' => 'VIEW_PRODUCTS_PAYLOAD'
            ],
            [
                'type' => 'postback',
                'title' => 'Hablar con Agente',
                'payload' => 'TALK_TO_AGENT_PAYLOAD'
            ],
            [
                'type' => 'web_url',
                'url' => 'https://example.com/help',
                'title' => 'Centro de Ayuda'
            ]
        ]
    ]
];

$result = Instagram::message()
    ->withAccessToken($account->access_token)
    ->withInstagramUserId($account->instagram_business_account_id)
    ->sendGenericTemplate('807022408557003', $elements);









use ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount;
use ScriptDevelop\InstagramApiManager\Facades\Instagram;
$account = InstagramBusinessAccount::first();

$buttons = [
        [
            'type' => 'web_url',
            'url' => 'https://tu-sitio.com/catalogo',
            'title' => 'Ver Catálogo'
        ],
        [
            'type' => 'postback',
            'payload' => 'SPEAK_WITH_AGENT',
            'title' => 'Hablar con Agente'
        ],
        [
            'type' => 'postback',
            'payload' => 'VIEW_ORDER_STATUS',
            'title' => 'Estado de Pedido'
        ]
    ];
    
    $result = Instagram::message()
        ->withAccessToken($account->access_token)
        ->withInstagramUserId($account->instagram_business_account_id)
        ->sendButtonTemplate(
            '807022408557003', 
            '¡Bienvenido! ¿En qué podemos ayudarte hoy?', 
            $buttons
        );






use ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount;
use ScriptDevelop\InstagramApiManager\Facades\Instagram;

$account = InstagramBusinessAccount::first();

// Para usar el nuevo servicio de menú persistente
$buttons = [
    Instagram::persistentMenu()->createPostbackButton('Ver Productos', 'VIEW_PRODUCTS'),
    Instagram::persistentMenu()->createPostbackButton('Hablar con Agente', 'TALK_TO_AGENT'),
    Instagram::persistentMenu()->createUrlButton('Visitar Sitio', 'https://example.com', 'full')
];

$menu = Instagram::persistentMenu()->createLocalizedMenu('default', false, $buttons);

$result = Instagram::persistentMenu()
    ->withAccessToken($account->access_token)
    ->withInstagramUserId($account->instagram_business_account_id)
    ->setPersistentMenu([$menu]);

1. Obtener el menú actual
   $currentMenu = Instagram::persistentMenu()
    ->withAccessToken($account->access_token)
    ->withInstagramUserId($account->instagram_business_account_id)
    ->getPersistentMenu();

    print_r($currentMenu);
    
2. Eliminar el menú
    $deleteResult = Instagram::persistentMenu()
    ->withAccessToken($account->access_token)
    ->withInstagramUserId($account->instagram_business_account_id)
    ->deletePersistentMenu();

    print_r($deleteResult);

3. Crear menús localizados
   // Menú en español
    $spanishButtons = [
        Instagram::persistentMenu()->createPostbackButton('Ver Catálogo', 'VIEW_CATALOG'),
        Instagram::persistentMenu()->createPostbackButton('Soporte Técnico', 'TECH_SUPPORT'),
        Instagram::persistentMenu()->createUrlButton('Nuestro Sitio', 'https://tu-sitio.com', 'full')
    ];

    $spanishMenu = Instagram::persistentMenu()->createLocalizedMenu('es_ES', false, $spanishButtons);

    // Menú en inglés (default)
    $englishButtons = [
        Instagram::persistentMenu()->createPostbackButton('View Catalog', 'VIEW_CATALOG'),
        Instagram::persistentMenu()->createPostbackButton('Technical Support', 'TECH_SUPPORT'),
        Instagram::persistentMenu()->createUrlButton('Our Website', 'https://your-site.com', 'full')
    ];

    $englishMenu = Instagram::persistentMenu()->createLocalizedMenu('default', false, $englishButtons);

    // Configurar ambos menús
    $result = Instagram::persistentMenu()
        ->withAccessToken($account->access_token)
        ->withInstagramUserId($account->instagram_business_account_id)
        ->setPersistentMenu([$englishMenu, $spanishMenu]);

Probar la funcionalidad de los botones
Para botones de postback:
Haz clic en "Ver Productos" o "Hablar con Agente" en Instagram

Tu webhook recibirá un evento messaging_postbacks con el payload correspondiente

Puedes manejar estos postbacks en tu controlador de webhook

Para botones de URL:
Haz clic en "Visitar Sitio"

Se abrirá la URL en el navegador dentro de la app de Instagram

Verificación adicional
Puedes verificar que el menú se configuró correctamente obteniendo el menú actual:

Consideraciones importantes
Tiempo de actualización: El menú puede tardar unos minutos en aparecer en todas las conversaciones

Conversaciones existentes: Los usuarios deben refrescar su bandeja de entrada para ver los cambios

Nuevas conversaciones: Verán el menú actualizado inmediatamente

Límites: Máximo 5 botones por menú y 640 caracteres para el texto

¡El hecho de que hayas recibido "result": "success" indica que todo está funcionando correctamente! Ahora solo necesitas abrir Instagram para ver el menú en acción.





1. Establecer Ice Breakers


use ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount;
use ScriptDevelop\InstagramApiManager\Facades\Instagram;

$account = InstagramBusinessAccount::first();

// Crear acciones de ice breaker
$actions = [
    Instagram::persistentMenu()->createIceBreakerAction('¿Cuáles son sus horarios?', 'HORARIOS_PAYLOAD'),
    Instagram::persistentMenu()->createIceBreakerAction('¿Dónde están ubicados?', 'UBICACION_PAYLOAD'),
    Instagram::persistentMenu()->createIceBreakerAction('¿Qué productos ofrecen?', 'PRODUCTOS_PAYLOAD'),
    Instagram::persistentMenu()->createIceBreakerAction('¿Cómo contacto a un agente?', 'CONTACTO_PAYLOAD')
];

// Crear ice breaker
$iceBreaker = Instagram::persistentMenu()->createIceBreaker('default', $actions);

// Establecer ice breakers
$result = Instagram::persistentMenu()
    ->withAccessToken($account->access_token)
    ->withInstagramUserId($account->instagram_business_account_id)
    ->setIceBreakers([$iceBreaker]);

2. Obtener Ice Breakers Actuales

    $iceBreakers = Instagram::persistentMenu()
    ->withAccessToken($account->access_token)
    ->withInstagramUserId($account->instagram_business_account_id)
    ->getIceBreakers();


3. Eliminar Ice Breakers

    $result = Instagram::persistentMenu()
    ->withAccessToken($account->access_token)
    ->withInstagramUserId($account->instagram_business_account_id)
    ->deleteIceBreakers();









Generar enlaces ig.me

use ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount;
use ScriptDevelop\InstagramApiManager\Facades\Instagram;

$account = InstagramBusinessAccount::first();

// Generar enlace simple
$link = $account->getIgMeLink();
// o
$link = Instagram::link()->generateIgMeLink($account);

// Generar enlace con parámetro de referencia
$linkWithRef = $account->getIgMeLink('campaign_123');
// o
$linkWithRef = Instagram::link()->generateIgMeLink($account, 'campaign_123');

// Generar código QR
$qrCode = Instagram::link()->generateIgMeQrCode($account, 'campaign_123', 500);


Obtener estadísticas de referrals

$stats = Instagram::link()->getReferralStats($account->instagram_business_account_id, 'campaign_123');











Extensión y personalización
Puedes extender los modelos para añadir relaciones y eventos personalizados.

Crear comandos Artisan para sincronización programada.

Añadir nuevas funciones al ApiClient conforme evoluciona la API de Instagram.

Contribuir
¡Contribuciones son bienvenidas! Abre issues para reportar bugs o solicitar funciones, y pull requests para proponer mejoras.

Licencia
MIT License © [Tu Nombre, ScriptDevelop]

Contacto
Para dudas o soporte, abre un Issue en el repositorio oficial o contáctanos vía email.

Gracias por usar Instagram API Manager for Laravel.
















# Instagram API Manager for Laravel

[![Latest Version](https://img.shields.io/github/release/ScriptDevelop/instagram-api-manager.svg)](https://github.com/ScriptDevelop/instagram-api-manager/releases)
[![License](https://img.shields.io/github/license/ScriptDevelop/instagram-api-manager.svg)](https://github.com/ScriptDevelop/instagram-api-manager/blob/main/LICENSE)

---

## Introducción

Instagram API Manager es un paquete Laravel diseñado para facilitar la integración y gestión avanzada de cuentas Instagram Business mediante la API Graph y Messenger API oficial de Meta. Este paquete ofrece:

- Administración centralizada de cuentas Instagram vinculadas a Facebook Pages.
- Manejo automático de tokens de acceso.
- Gestión de mensajes, conversaciones y multimedia en Instagram Direct.
- Recepción y manejo de Webhooks para eventos en tiempo real.
- Herramientas y convenciones listas para producción en Laravel 12+.

---

## Descripción del Paquete

Este paquete provee una solución robusta para desarrolladores y empresas que requieren integrar Instagram Business de forma completa a sus aplicaciones Laravel. Cuenta con modelos Eloquent optimizados, integración con la API oficial mediante un cliente HTTP configurado con logging personalizado, y soporte completo para Webhooks con verificación automática.

---

## Requisitos

- PHP >= 8.1
- Laravel >= 12
- Composer
- Cuenta Facebook con Instagram Business vinculada
- Permisos y configuraciones en Meta for Developers para la app y acceso API

---

## Instalación

1. Instalar el paquete via Composer:

    ```bash
    composer require scriptdevelop/instagram-api-manager
    ```


2. Publicar configuraciones y recursos necesarios:

    ```bash
        # Publicar todo junto
        php artisan vendor:publish --tag=instagram-api-manager

        # O publicar por partes
        php artisan vendor:publish --tag=instagram-migrations
        php artisan vendor:publish --tag=instagram-config
        php artisan vendor:publish --tag=instagram-callback-routes
        php artisan vendor:publish --tag=instagram-webhook-routes
        php artisan vendor:publish --tag=instagram-logging

    ```


3. Ejecutar migraciones:

    ```bash
        php artisan migrate
    ```


4. Agregar la variable de entorno para verificación de Webhook en tu archivo `.env`:

    ```bash
        INSTAGRAM_WEBHOOK_VERIFY_TOKEN=tu_token_seguro_personalizado
    ```


5. Actualizar el archivo `config/instagram.php` si deseas cambiar valores por defecto (opcional).

6. En `app/Http/Middleware/VerifyCsrfToken.php` agregar la excepción para la ruta del webhook:


Alternativamente, aplica la exclusión CSRF según tu setup en `bootstrap/app.php` o Provider, para permitir peticiones externas seguras.

7. Integra y adapta la ruta webhook publicada en `routes/instagram_webhook.php` para recibir eventos de Instagram.

---

## Uso Básico

### Configuración de conexión API

Configura en tu `.env`:

    ```bash
        INSTAGRAM_API_BASE_URL=https://graph.facebook.com
        INSTAGRAM_API_VERSION=v23.0
        INSTAGRAM_WEBHOOK_VERIFY_TOKEN=tu_token_seguro
    ```


El paquete utilizará esta configuración automáticamente.

---

### Manejo de Cuentas

- Obtención y almacenamiento de las páginas y cuentas Instagram Business vinculadas usando el endpoint `GET_USER_MANAGED_PAGES`.
- Uso de modelos Eloquent:
    - `InstagramBusinessAccount`
    - `InstagramProfile`
    - `InstagramConversation`
    - `InstagramMessage`
    - `InstagramContact`
  
Puedes crear servicios o jobs que sincronicen cuentas y datos usando el cliente ApiClient del paquete.

---

### Envío y Recepción de Mensajes

- Utiliza el cliente API para enviar mensajes de texto, multimedia y plantillas mediante métodos que usan el endpoint `SEND_TEXT_MESSAGE` y variantes.
- Recibe mensajes y eventos en tiempo real a través del webhook en la ruta `/instagram-webhook`.
- Procesa eventos dentro de `InstagramWebhookController` o delega la lógica a otros servicios.

---

### Multimedia y Reels

- Sube Reels a contenedores con `UPLOAD_REEL_CONTAINER`.
- Consulta estado mediante `GET_IG_CONTAINER_STATUS`.
- Publica Reels usando `PUBLISH_REEL`.
- Sube attachments para mensajes (fotos, GIFs) con `UPLOAD_MESSAGE_ATTACHMENT`.

---

### Logging Personalizado

El paquete utiliza un canal de log Laravel llamado `instagram` configurado para guardar logs en `storage/logs/instagram.log`. Para habilitarlo debe agregarse el canal en `config/logging.php`:

    ```php
        'instagram' => [
            'driver' => 'daily',
            'path' => storage_path('logs/instagram.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'tap' => [\ScriptDevelop\InstagramApiManager\Logging\CustomizeFormatter::class],
        ],
    ```


---

## Publicar y Configurar Webhook (Paso a paso)

1. El webhook recibe la verificación de Meta para suscribirse automáticamente al enviar el token configurado en `INSTAGRAM_WEBHOOK_VERIFY_TOKEN`.
2. El webhook recibe eventos POST para manejar mensajes, reacciones y más.
3. La ruta `/instagram-webhook` está registrada y excluida de CSRF para evitar bloqueos.
4. Puedes personalizar el controlador `InstagramWebhookController` según conveniencia.

---

## Extensión y Personalización

- Puedes extender los modelos para añadir relaciones o eventos.
- Crear comandos Artisan para sincronización programada.
- Añadir nuevas funciones al ApiClient según la evolución de la API de Instagram.

---

## Contribuir

¡Contribuciones son bienvenidas! Por favor abre issues para bugs o solicitudes de funciones y pull requests para mejoras.

---

## Licencia

MIT License © [Tu Nombre, ScriptDevelop]

---

## Contacto

Para dudas o soporte, abre un Issue en el repositorio oficial o contáctanos vía email.

---

## 📱 Sistema de Recepción de Mensajes de Instagram (v1.0.60+)

### ✨ Nuevo: Webhook Mejorado con Logging Detallado

A partir de la versión 1.0.60, el sistema de recepción de mensajes de Instagram tiene un logging mejorado que te permite ver claramente:

✅ Cuándo llega un mensaje
✅ Cómo se procesa
✅ Dónde se almacena en BD
✅ Cualquier error que ocurra

### 🚀 Cómo Usar

#### 1. Ver Logs en Vivo
```powershell
Get-Content -Path "storage/logs/instagram.log" -Wait
```

#### 2. Testear el Webhook
```bash
php artisan instagram:test-webhook --type=message
php artisan instagram:test-webhook --type=postback
php artisan instagram:test-webhook --type=image
```

#### 3. Ver Mensajes en BD
```bash
php artisan tinker
>>> DB::table('instagram_messages')->count()
>>> DB::table('instagram_messages')->latest()->first()
```

### 📊 Flujo de Recepción

```
Usuario envía mensaje en Instagram
           ↓
Instagram envía POST al webhook
           ↓
Sistema recibe y valida
           ↓
Busca cuenta de negocio en BD
           ↓
Busca o crea conversación
           ↓
Procesa según tipo (texto, imagen, postback, etc)
           ↓
Almacena en tabla: instagram_messages
           ↓
Logea confirmación con ID del registro
```

### 📚 Documentación Completa

- **[DOCUMENTACION_INDEX.md](DOCUMENTACION_INDEX.md)** - Índice de toda la documentación
- **[IMPLEMENTACION_COMPLETADA.md](IMPLEMENTACION_COMPLETADA.md)** - Resumen de cambios
- **[WEBHOOK_FLOW.md](WEBHOOK_FLOW.md)** - Flujo técnico detallado
- **[WEBHOOK_IMPLEMENTATION.md](WEBHOOK_IMPLEMENTATION.md)** - Guía práctica de uso
- **[FLUJO_VISUAL.txt](FLUJO_VISUAL.txt)** - Diagrama visual ASCII

### 🧪 Tests Incluidos

```bash
# Ejecutar todos los tests del webhook
php artisan test --filter="InstagramWebhookMessagesTest"
```

### 💡 Tips

- Los logs usan **emojis** para identificar rápidamente eventos
- El comando `instagram:test-webhook` es perfecto para debugging
- Todos los mensajes tienen **ID único** en BD
- Las **conversaciones** se crean automáticamente
- Los **duplicados** se descartan automáticamente

---

Gracias por usar **Instagram API Manager for Laravel**.
