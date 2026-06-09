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

### 6. Asistente de Instalación (Wizard)

Desde la versión 1.0.78, el paquete incluye un asistente interactivo:

```bash
php artisan instagram:install
```

El wizard te guía paso a paso por:
1. Publicación de archivos de configuración
2. Ejecución opcional de migraciones
3. Creación de enlace simbólico de storage
4. Exclusión automática de CSRF en `bootstrap/app.php`
5. Publicación opcional de rutas
6. Impresión de variables de entorno requeridas

Para uso en producción sin interacción, podés skipear prompts con `--no-interaction` o usar el flag `--force` para sobrescribir configs existentes.

### 7. Instalación Manual (paso a paso)

Si preferís no usar el wizard, acá está el detalle:

```bash
# 1. Instalar el paquete
composer require scriptdevelop/instagram-api-manager

# 2. Publicar configs
php artisan vendor:publish --tag=instagram-facebook-config

# 3. Publicar migraciones
php artisan vendor:publish --tag=instagram-migrations

# 4. Publicar rutas de webhook
php artisan vendor:publish --tag=instagram-webhook-routes
php artisan vendor:publish --tag=facebook-webhook-routes

# 5. Publicar canales de broadcast (Reverb)
php artisan vendor:publish --tag=instagram-channels

# 6. Crear enlace simbólico para storage de media
php artisan storage:link

# 7. Ejecutar migraciones
php artisan migrate

# 8. Agregar variables de entorno
# (ver .env.example generado en el paso 2)

# 9. Excluir CSRF en bootstrap/app.php (Laravel 11+):
# $middleware->validateCsrfTokens(except: [
#     'instagram-webhook/*',
#     'facebook-webhook/*',
#     'instagram/callback',
#     'facebook/callback',
# ]);
```

### 8. Verificación Post-Instalación

```bash
# Verificar que los comandos están registrados
php artisan list | grep -E "instagram:|messenger:"

# Verificar que las rutas están cargadas
php artisan route:list | grep -E "webhook|callback"

# Verificar que las migraciones están disponibles
php artisan migrate:status
```

Si todo aparece correctamente, el paquete está listo. Proceder a [02-configuracion.md](02-configuracion.md) para conectar con Meta.

---
[◄◄ Inicio](../../README.md) | [Configurar API ►►](02-configuracion.md)
