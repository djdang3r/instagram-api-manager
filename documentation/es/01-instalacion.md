[◄◄ Inicio](../../README.md)
[▲ Tabla de contenido](00-tabla-de-contenido.md)
[Configurar API ►►](02-configuracion.md)

# 🚀 Instalación

Este paquete está diseñado específicamente para Laravel 12.x y requiere PHP 8.2+.

### 1. Requisitos previos

Antes de comenzar, asegúrate de tener:
- Una cuenta de Facebook Developer.
- Una App de Meta configurada con los productos **Instagram Graph API** y **Messenger API for Instagram**.
- Una cuenta de Instagram Business vinculada a una Fan Page de Facebook.

### 2. Instalación vía Composer

Ejecuta el siguiente comando en tu terminal:

```bash
composer require scriptdevelop/instagram-api-manager
```

### 3. Publicar recursos

Puedes publicar todos los recursos del paquete (configuración, migraciones, rutas, etc.) con un solo comando:

```bash
php artisan vendor:publish --tag=instagram-api-manager
```

O si prefieres un control más granular:

```bash
# Publicar solo migraciones
php artisan vendor:publish --tag=instagram-migrations

# Publicar solo configuración
php artisan vendor:publish --tag=instagram-config

# Publicar solo rutas de callback/webhook
php artisan vendor:publish --tag=instagram-callback-routes
php artisan vendor:publish --tag=instagram-webhook-routes

# Publicar solo logging configurado
php artisan vendor:publish --tag=instagram-logging
```

### 4. Ejecutar Migraciones

Crea las tablas necesarias en tu base de datos:

```bash
php artisan migrate
```

### 5. Configuración de Webhook

Añade el token de verificación a tu archivo `.env`:

```env
INSTAGRAM_WEBHOOK_VERIFY_TOKEN=tu_token_seguro_personalizado
```

> [!IMPORTANT]
> No olvides excluir la ruta del webhook del middleware CSRF en `bootstrap/app.php` (Laravel 11+) o `VerifyCsrfToken.php` (versiones anteriores).

---
[◄◄ Inicio](../../README.md) | [Configurar API ►►](02-configuracion.md)
