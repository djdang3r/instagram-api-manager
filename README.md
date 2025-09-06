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
        INSTAGRAM_API_VERSION=v19.0
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
