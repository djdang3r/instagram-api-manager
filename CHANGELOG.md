# Changelog

Todos los cambios notables de este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

---

## [1.0.78] - 2026-05-08

### Added
- **Asistente de instalación interactivo**: Nuevo comando `php artisan instagram:install` que guía al usuario paso a paso en la configuración inicial del paquete usando Laravel Prompts:
  - Publicación de archivos de configuración (`instagram.php`, `facebook.php`)
  - Publicación y ejecución opcional de migraciones
  - Creación de enlace simbólico de storage
  - Exclusión automática de rutas de CSRF (`instagram-webhook/*`, `instagram/callback`) en `bootstrap/app.php`
  - Publicación opcional de rutas (webhook, callback, canales broadcast)
  - Impresión de variables de entorno requeridas al finalizar
- **Hook post-install de Composer**: Al instalar el paquete vía `composer require`, se muestra un mensaje de éxito con los siguientes pasos y las variables de entorno necesarias. Implementado en `ComposerInstaller::postInstall()`.
- **Dependencia `laravel/prompts`**: Agregada para el asistente interactivo (`confirm`, `intro`, `outro`, `note`, `spin`, `warning`).

### Changed
- `InstagramServiceProvider`: registrado el nuevo comando `InstallInstagramApiManager`.

---

## [1.0.77] - 2026-05-08

### Changed
- **Eventos broadcast ahora usan `ShouldBroadcastNow`**: Los 7 eventos broadcast (`InstagramMessageReceived`, `InstagramPostbackReceived`, `InstagramReactionReceived`, `InstagramOptinReceived`, `InstagramReferralReceived`, `InstagramReadReceived`, `InstagramMessageEdited`) ahora implementan `ShouldBroadcastNow` en lugar de `ShouldBroadcast`. Esto garantiza que los eventos se transmitan **inmediatamente** sin depender de un worker de cola — antes no funcionaban si `queue:work` no estaba corriendo.

### Fixed
- **Modelo `instagram_conversation` faltante en configuración**: Agregada la key `instagram_conversation` en `config('instagram.models.*')` que no existía, impidiendo la resolución dinámica del modelo de conversaciones.
- **Key de modelo inconsistente**: Renombrada `message` → `instagram_message` en `config('instagram.models.*')` para mantener consistencia de nomenclatura con el resto de modelos (`instagram_profile`, `instagram_contact`, etc.).

### Added
- **Log de debug en `BaseWebhookProcessor`**: Agregado log informativo que registra el tipo de evento y la clase que se está disparando, facilitando la depuración del sistema de broadcast.

---

## [1.0.75] - 2026-05-07

### Added
- **Sistema de Eventos Broadcast con Laravel Reverb**: El paquete ahora dispara 7 eventos broadcast para todos los tipos de mensajes de Instagram recibidos vía webhook. Los eventos pueden escucharse desde el frontend con Laravel Echo o desde el backend con listeners de Laravel.
  - `InstagramMessageReceived` — mensajes entrantes (texto, imagen, video, audio, adjuntos, quick reply)
  - `InstagramPostbackReceived` — clics en botones CTA
  - `InstagramReactionReceived` — reacciones a mensajes
  - `InstagramOptinReceived` — aceptación de recepción de mensajes
  - `InstagramReferralReceived` — llegada por enlace de referencia (ig.me, anuncios)
  - `InstagramReadReceived` — confirmación de lectura de mensajes enviados
  - `InstagramMessageEdited` — edición de mensajes por el usuario
- **Procesador de Webhook Personalizable**: El procesamiento de webhooks ahora sigue el patrón Strategy. Los usuarios pueden reemplazar completamente la lógica de procesamiento implementando `WebhookProcessorInterface` y registrando su clase en `config('instagram.webhook.processor')`.
  - Nueva interfaz: `Contracts/WebhookProcessorInterface` con métodos `handle()`, `verifyWebhook()`, `processWebhookPayload()`
  - Implementación por defecto: `Services/WebhookProcessors/BaseWebhookProcessor` que envuelve `InstagramMessageService` y dispara eventos broadcast
  - `InstagramWebhookController` refactorizado como delegador fino (44 líneas) — idéntico al patrón de WhatsApp
- **Configuración de Broadcast**: Nuevas claves en `config/instagram.php`:
  - `broadcast.channel_type` — `public` o `private` (env `INSTAGRAM_BROADCAST_CHANNEL_TYPE`)
  - `broadcast.custom_channels` — permite usar rutas de canal propias (env `INSTAGRAM_CUSTOM_CHANNELS`)
  - `events.*` — mapeo de tipos de eventos a clases FQCN (totalmente personalizables)
  - `webhook.processor` — FQCN del procesador de webhook (env `INSTAGRAM_WEBHOOK_PROCESSOR`)
- **Rutas de Canal**: Nuevo archivo `src/routes/channels.php` con autorización de canales broadcast. Publicable al proyecto del usuario vía `php artisan vendor:publish --tag=instagram-channels`.
- **Documentación completa en español**:
  - Nueva guía `documentation/es/08-eventos-tiempo-real.md` con arquitectura, configuración, ejemplos de frontend (Echo, Alpine.js, Livewire), backend (listeners), y solución de problemas
  - Actualización de `07-webhooks.md` con sección de procesador personalizable
  - Actualización de `02-configuracion.md` con nuevas variables de entorno
  - Actualización de `00-tabla-de-contenido.md` con enlace a la nueva guía
- **Diseño de Coexistencia con WhatsApp API Manager**: Ambos paquetes pueden instalarse simultáneamente sin conflictos. Canales, configuraciones, namespaces e interfaces usan prefijos independientes (`instagram-` vs `whatsapp-`).

### Changed
- `InstagramWebhookController` refactorizado como delegador fino que resuelve y delega al `WebhookProcessorInterface`
- `InstagramServiceProvider` actualizado: binding de la interfaz del procesador, carga condicional de rutas de canal, publicación de channels.php

### Fixed
- **OAuth Instagram**: Agregado `resolveInstagramOAuthBaseUrl()` en `InstagramAccountService` que detecta cuando `INSTAGRAM_OAUTH_BASE_URL` está mal configurada apuntando a Facebook y aplica fallback a `https://api.instagram.com`

---

## [1.0.74] - 2026-04-25

### Fixed
- Ajuste para obtener adecuadamente la URL de la petición en el callback de Instagram (intermediate redirect handling con `l.instagram.com`)

---

[Unreleased]: https://github.com/ScriptDevelop/instagram-api-manager/compare/v1.0.78...HEAD
[1.0.78]: https://github.com/ScriptDevelop/instagram-api-manager/compare/v1.0.77...v1.0.78
[1.0.77]: https://github.com/ScriptDevelop/instagram-api-manager/compare/v1.0.75...v1.0.77
[1.0.75]: https://github.com/ScriptDevelop/instagram-api-manager/compare/v1.0.74...v1.0.75
[1.0.74]: https://github.com/ScriptDevelop/instagram-api-manager/releases/tag/v1.0.74
