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

Gracias por usar **Instagram API Manager for Laravel**.
