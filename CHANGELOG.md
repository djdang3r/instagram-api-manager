# Changelog

Todos los cambios notables de este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.1.1] - 2026-06-06

Patch release: cierra issues de auditoría del 1.1.0, agrega foreign key constraints diferidas, y completa la documentación ES + mirror EN.

### Fixed
- **`MessengerMessageService::downloadMediaFile()` ignoraba el parámetro `$filename`**: El caller en `updateOrCreateContact()` pasaba `$messengerUserId . '.jpg'` pero el código generaba un nuevo `uniqid('msg_')` siempre. Ahora el filename se respeta si se pasa.
- **PHPDoc faltante en bindings `facebook.message` vs `messenger.message`**: Dos servicios distintos (`FacebookMessageService` para envío OUTBOUND, `MessengerMessageService` para procesamiento INBOUND) tenían nombres confusos. Agregados comentarios PHPDoc para clarificar el uso.

### Added
- **Foreign key constraints** en las 11 migraciones nuevas del 1.1.0:
  - `instagram_account_stats.instagram_business_account_id` → `instagram_business_accounts.id` (CASCADE)
  - `instagram_media_stats.instagram_business_account_id` → `instagram_business_accounts.id` (CASCADE)
  - `facebook_page_stats.page_id` → `facebook_pages.page_id` (CASCADE)
  - `messenger_insights.page_id` → `facebook_pages.page_id` (CASCADE)
  - `instagram_comments.instagram_business_account_id` → `instagram_business_accounts.id` (CASCADE)
  - `instagram_posts.instagram_business_account_id` → `instagram_business_accounts.id` (CASCADE)
  - `instagram_stories.instagram_business_account_id` → `instagram_business_accounts.id` (CASCADE)
  - `instagram_media_posts.instagram_business_account_id` → `instagram_business_accounts.id` (CASCADE)
  - `facebook_posts.page_id` → `facebook_pages.page_id` (CASCADE)
  - `facebook_comments.page_id` → `facebook_pages.page_id` (CASCADE)
  - `facebook_media.page_id` → `facebook_pages.page_id` (CASCADE)
- **Documentación completa en español (todos los 9 docs a 200+ líneas)**: Cada doc ahora incluye Quick Start, Configuración, Endpoints/Webhooks, Ejemplos de código, Errores Comunes, Configuration Reference, FAQ, y secciones específicas del dominio.
- **Mirror completo a inglés** de los 9 docs nuevos (`documentation/en/08-*.md` a `16-*.md`).
- **Documentación actualizada**:
  - `documentation/es/01-instalacion.md`: Agregada sección 6 (wizard) y 7 (manual paso a paso)
  - `documentation/es/02-configuracion.md`: Agregadas 22 nuevas env vars de v1.1.0
  - `documentation/es/messenger/01-autenticacion.md`: Agregadas Custom Redirect URLs
  - `documentation/es/messenger/03-webhooks.md`: Agregados nuevos webhook fields 2026 (`message_edit`, `inbox_labels`, `standby`, `messaging_account_linking`, `messaging_feedback`)
  - Mirror en `documentation/en/01-installation.md` y `02-configuration.md`

### Notes
- **No breaking changes** desde 1.1.0
- **No migration changes** que requieran re-ejecutar: las FKs se aplican solo en installs fresh. Para installs existentes, las FKs deben agregarse manualmente vía migration nueva.

---

## [1.1.0] - 2026-06-06

Release de integración: combina los 46 commits del colaborador (`vientoquesurcalosmares`, PRs #11-#14) con el WIP del usuario (44 archivos untracked + 25 modificados). Cierra bugs P0/P1 de ambos lados, completa documentación y CHANGELOG.

### Added
- **Facebook Messenger — Mensajería completa**: Implementación de todo el ecosistema de mensajería de Facebook Messenger.
  - Nuevo `MessengerWebhookProcessor` dedicado (webhook en `/facebook-webhook`, `"object": "page"`)
  - `MessengerMessageService` para procesamiento inbound de mensajes (texto, imagen, audio, video, archivo, sticker, quick reply, postback, reacción, read receipt, referral, message edit, optin)
  - `FacebookMessageService` reconstruido con 14 métodos de envío: `sendTextMessage`, `sendImageMessage`, `sendAudioMessage`, `sendVideoMessage`, `sendFileMessage`, `sendStickerMessage`, `sendQuickReplies`, `sendGenericTemplate`, `sendButtonTemplate`, `sendReadReceipt`, `sendReaction`, `sendReply`, `sendMultipleImages`, `sendTaggedMessage`
  - `uploadAttachment` para subir media reusable a servidores Meta (`/message_attachments`)
  - 8 eventos broadcast para Messenger (canal `facebook-messages`) con soporte Laravel Reverb
  - Ruta `/facebook/connect` con método `connect()` en `FacebookAuthController`
  - `MessengerWebhookController` para manejo de webhooks
  - Tabla `messenger_media_messages` para separar archivos multimedia de mensajes
- **Migraciones**: 4 nuevas migraciones (campos Messenger, tabla media, contactos, conversaciones)
- **Configuración de archivos multimedia**: Secciones `media` en `config/instagram.php` y `config/facebook.php` con rutas de almacenamiento configurables por `.env`
- **Seguridad**: Validación `X-Hub-Signature-256` (SHA256) en webhooks de Instagram y Messenger vía trait `ValidatesHubSignature`
- **Rate limiting**: `throttle:60,1` en rutas de webhook (`/instagram-webhook`, `/facebook-webhook`)
- **Instagram — Métodos de envío faltantes**: `sendReaction`, `sendReply`, `sendMultipleImages`, `uploadAttachment` agregados a `InstagramMessageService`
- **Soporte de archivos locales**: `sendImageMessage`, `sendAudioMessage`, `sendVideoMessage`, `sendFileMessage`/`sendDocumentMessage` y `sendMultipleImages` ahora aceptan tanto URL como archivos locales (`SplFileInfo` o ruta string). Nuevo método `sendMediaRequest()` en `ApiClient` con soporte `multipart/form-data`.
- **Token refresh para Facebook**: `FacebookAccountService` ahora tiene `refreshLongLivedToken()` y `refreshAndStoreLongLivedToken()`
- **Validación de ventana 24h**: `isWithin24hWindow()` en ambos servicios de mensajería previene envíos fuera de ventana sin `MESSAGE_TAG`

### Changed
- **Versión API por defecto**: `v19.0` → `v25.0` (última disponible a Mayo 2026) en `config/instagram.php` y `config/facebook.php`
- **Unificación de ApiClient**: Un solo Guzzle Client compartido (singleton) + instancias transient de ApiClient con configuración fluida. Eliminados 8 `new ApiClient(...)` de servicios.
- **Wizard de instalación**: Actualizado con variables de entorno de Facebook y versión corregida
- **Broadcast Messenger**: Configuración `facebook.broadcast.channel_type` independiente de Instagram. Canal `facebook-messages` autorizado en `channels.php`
- **Soporte de archivos locales**: `sendImageMessage`, `sendAudioMessage`, `sendVideoMessage`, `sendFileMessage`/`sendDocumentMessage` y `sendMultipleImages` ahora aceptan tanto URL como archivos locales (`SplFileInfo` o ruta string). El paquete detecta automáticamente y usa `multipart/form-data` con `filedata` para archivos locales. Nuevo método `sendMediaRequest()` en `ApiClient`.

### Removed
- **Tests obsoletos**: `tests/Feature/InstagramWebhookMessagesTest.php` eliminado (los tests se ejecutan desde el proyecto consumidor)

### Fixed
- **`config/logging-additions.php` faltante**: El archivo de configuración de logging referenciado por el ServiceProvider no existía, causando error en `vendor:publish --tag=instagram-logging`. Creado con canales `instagram` y `facebook` usando stack driver con toggle vía `.env` (`INSTAGRAM_LOGGING_ENABLED`, `FACEBOOK_LOGGING_ENABLED`).
- **Ruta webhook Messenger no publicable**: La ruta `/facebook-webhook` estaba hardcodeada en el ServiceProvider, impidiendo su personalización. Movida a archivo `routes/facebook_webhook.php` con tag `facebook-webhook-routes` para `vendor:publish`.
- **Wizard de instalación**: Agregada opción de publicar ruta Messenger, explicación de canales de logging con toggle, y variables `.env` de logging en output final.

### Added (nuevas features 1.1.0)
- **Integración completa con collaborator PRs #11-#14**:
  - **Custom redirect URLs**: 3 nuevas claves por plataforma en `config/facebook.php` y `config/instagram.php` (`*_CUSTOM_REDIRECT_SUCCESS_URL`, `*_CUSTOM_REDIRECT_ERROR_URL`, `*_CUSTOM_REDIRECT_WARNING_URL`) para redirigir después del OAuth
  - **Encriptación de credenciales en `MetaApp`**: Mutators de Laravel Crypt para `access_token`, `verify_token` y `app_secret` — nada en plaintext
  - **Paginación completa de `me/accounts`**: `FacebookAccountService::handleCallback()` itera con `while` loop para soportar >25 páginas
  - **Download automático de profile picture**: `MessengerMessageService::updateOrCreateContact()` con cache local de fotos de perfil (toggle vía `FACEBOOK_DOWNLOAD_USER_PROFILE_PICTURE`)
  - **Relaciones `replies()` y `replyToMessage()`** en `MessengerMessage` para navegación de hilos
  - **Índices de performance `processRead`**: 3 índices compuestos en `messenger_messages` (`conv+method+created_time`, `conv+status+created_time`, `conv+method+read_at+created_time`) + campo `conversation_id` ahora nullable en `messenger_conversations`
  - **Dedup por `media_url_hash` SHA256**: índice compuesto en `messenger_media_messages` evita descargar media duplicada
  - **`pages_manage_metadata` scope** en OAuth URL — necesario para suscribir webhooks después
  - **Refactor a `InstagramModelResolver`**: 6 archivos migrados de modelos directos a resolver dinámico (configurable via `config('instagram.models.*')`)
- **7 nuevos service bindings** registrados en `InstagramServiceProvider`:
  - `instagram.comment` → `InstagramCommentService`
  - `instagram.publishing` → `InstagramContentPublishingService`
  - `instagram.insights` → `InstagramInsightsService`
  - `facebook.profile` → `MessengerProfileService`
  - `facebook.link` → `MessengerLinkService`
  - `facebook.insights` → `MessengerInsightsService`
  - `facebook.handover` → `MessengerHandoverService`
- **12 nuevos modelos**: `FacebookComment`, `FacebookMedia`, `FacebookPageStats`, `FacebookPost`, `InstagramAccountStats`, `InstagramComment`, `InstagramMediaPost`, `InstagramMediaStats`, `InstagramPost`, `InstagramStory`, `MessengerInsights` (+ `ProcessWebhookJob` para async webhook processing)
- **11 nuevas migraciones** (2026_05_27_000009..19): `instagram_account_stats`, `instagram_media_stats`, `facebook_page_stats`, `messenger_insights`, `instagram_comments`, `instagram_posts`, `instagram_stories`, `instagram_media_posts`, `facebook_posts`, `facebook_comments`, `facebook_media`
- **3 nuevas migraciones del collaborator** (2026_05_27_000001 + 2026_05_28_000001..2): `local_profile_picture`, `process_read_indexes`, `dedup_lookup_index`
- **3 nuevos comandos Artisan** registrados: `SyncInstagramComments`, `SyncInstagramStats`, `SyncMessengerInsights` (+ `TestInstagramWebhook` que ya existía)
- **2 nuevos eventos broadcast**: `InstagramCommentReceived`, `InstagramMentionReceived` (con `ShouldBroadcastNow`)
- **Facades extendidas**: `Facebook::profile()`, `Facebook::link()`, `Facebook::insights()`, `Facebook::handover()` + `Instagram::comment()`, `Instagram::publishing()`, `Instagram::insights()`
- **Config keys adicionales**:
  - `facebook.rate_limit` con `max_attempts` y `decay_minutes` (formato Laravel estándar)
  - `facebook.broadcast.delivery_per_message` (bool, default `false`)
- **Documentación completa nueva** (español + mirror a inglés):
  - 9 nuevos documentos en `documentation/es/instagram/` y `documentation/es/messenger/` (Comments, Mentions, Publishing, Insights, Profile, Notifications, Handover)
  - Actualización de `01-instalacion.md` con todas las formas de instalación (composer, wizard, manual)
  - Actualización de `02-configuracion.md` con tabla completa de config keys
  - Actualización de `messenger/01-autenticacion.md` con custom redirect URLs
  - Actualización de `messenger/03-webhooks.md` con nuevos webhook fields 2026

### Changed (1.1.0)
- **7 conflictos merge resueltos** entre los 46 commits del collaborator y el WIP del usuario: `config/facebook.php`, `config/instagram.php`, `SyncMessengerConversations.php`, `InstagramServiceProvider.php`, `FacebookMessageService.php`, `MessengerMessageService.php`, `MessengerWebhookProcessor.php` + `routes/facebook_webhook.php`
- **`routes/facebook_webhook.php`** recuperado de `stash@{0}^3` (versión WIP del usuario, superior al del remoto) — GET/POST separados, throttle dinámico desde config, nombres de ruta separados
- **`MessengerLinkService`** migrado a `InstagramModelResolver` (consistencia con resto del paquete)
- **Rate limiting reforzado**: rutas de webhook separadas GET (10/min para verificación Meta) y POST (configurable), formato Laravel estándar `max_attempts`+`decay_minutes`

### Fixed (1.1.0)
- **`->indexed()` en 11 migrations** (CRITICAL P0): `$table->xxx()->indexed()` no es método de Laravel Schema Builder — fatal en `php artisan migrate` con `BadMethodCallException`. Reemplazado con `->index()` (24 casos) o eliminado tras `->unique()` (7 casos).
- **`ProcessWebhookJob::handle()` duplicado**: PHP fatal "Cannot redeclare handle()". Conservado el primer handle (con null check para soportar "sin firma" en queue).
- **`isWithin24hWindow` type-hint genérico `Model`**: Cambiado a `InstagramConversation` en `InstagramMessageService` y `MessengerConversation` en `FacebookMessageService` (type safety).
- **`downloadMediaFile` preservación de extensión**: El commit `69ddc32` del collaborator decía "preservar extensión" pero el código no lo hacía. Implementado método helper `extractUrlExtension()` que parsea la URL (sin query params) y usa la extensión real del archivo.
- **`googleapis.com/chart` API deprecada (2012)**: Reemplazada en `InstagramLinkService::generateIgMeQrCode()` y `MessengerLinkService::generateMmeQrCode()` — ahora retornan `null` con PHPDoc `@deprecated`. Usuario debe implementar su propio QR provider.
- **Migración `local_profile_picture` no idempotente**: Refactorizada con `Schema::hasColumn()` fuera del closure (siguiendo el patrón de v1.0.81).
- **Migración `dedup_lookup_index` no idempotente**: Refactorizada con `Schema::hasColumn()` antes de agregar `media_url_hash`.
- **`like_count` column type**: Cambiado de `string` a `integer` en `2026_05_27_000013_create_instagram_comments_table.php` (data integrity).
- **Inconsistencia `MessengerLinkService`**: Removido import de `FacebookPage` directo — ahora usa `InstagramModelResolver::facebook_page()` (consistencia con `MessengerMessageService`).

### Known Issues (diferido a 1.1.1+)
- **Foreign key constraints faltantes** en las 11 migraciones nuevas. Las columnas `instagram_business_account_id`, `page_id`, `post_id`, `media_id`, `comment_id`, etc. NO tienen FKs declaradas. Decisión de release: diferido a 1.1.1+ para evitar cambios de schema riesgosos en este release. Agregar manualmente o vía `Wave 6 Task 6.2` en próximo sprint.
- **Sin tests en el paquete**: El paquete se testea desde un proyecto Laravel consumidor. Decisión confirmada por el usuario (no se incluirá `tests/` ni `phpunit.xml`).

---

## [1.0.82] - 2026-05-08

### Fixed
- **Wizard `instagram:install` siempre sobrescribe migraciones**: La publicación de migraciones ahora usa `--force` siempre, no solo cuando se pasa `--force` al comando. Esto garantiza que si el usuario ya tenía migraciones publicadas de una versión anterior, el wizard las sobrescriba con las versiones corregidas del paquete. Combinado con el fix de `hasColumn` fuera del closure (v1.0.81), resuelve el ciclo de "migración rota publicada que nunca se actualiza".

---

## [1.0.81] - 2026-05-08

### Fixed
- **Migración `add_profile_fields_to_instagram_contacts_table` — `hasColumn` fuera del closure**: `Schema::hasColumn()` no funciona dentro de un `Schema::table()` porque MySQL lockea la tabla durante la alteración. Cada columna ahora tiene su propio bloque `Schema::table()` independiente con la verificación `hasColumn` hecha **antes** de abrir el closure. Esto resuelve definitivamente el error `Duplicate column name` (SQLSTATE 42S21).

---

## [1.0.80] - 2026-05-08

### Fixed
- **Migración idempotente `add_profile_fields_to_instagram_contacts_table`**: Las columnas `name`, `last_interaction_at`, `is_verified_user`, `follower_count`, `is_user_follow_business`, `is_business_follow_user` y `profile_synced_at` ahora verifican `Schema::hasColumn()` antes de intentar crearlas. Esto previene el error `Column already exists` (SQLSTATE 42S21) cuando la migración se ejecuta sobre una base de datos donde alguna de estas columnas ya fue agregada previamente.

---

## [1.0.79] - 2026-05-08

### Removed
- **Dependencia explícita `laravel/prompts` eliminada**: `laravel/prompts` ya viene incluido en `laravel/framework` (^12.0 || ^13.0). Declararlo como dependencia explícita con `^1.0` causaba conflictos en proyectos con versiones anteriores de Laravel que usan `laravel/prompts 0.x`, impidiendo la instalación del paquete.

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

[Unreleased]: https://github.com/ScriptDevelop/instagram-api-manager/compare/v1.1.1...HEAD
[1.1.1]: https://github.com/ScriptDevelop/instagram-api-manager/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/ScriptDevelop/instagram-api-manager/compare/v1.0.82...v1.1.0
[1.0.82]: https://github.com/ScriptDevelop/instagram-api-manager/compare/v1.0.81...v1.0.82
[1.0.81]: https://github.com/ScriptDevelop/instagram-api-manager/compare/v1.0.80...v1.0.81
[1.0.80]: https://github.com/ScriptDevelop/instagram-api-manager/compare/v1.0.79...v1.0.80
[1.0.79]: https://github.com/ScriptDevelop/instagram-api-manager/compare/v1.0.78...v1.0.79
[1.0.78]: https://github.com/ScriptDevelop/instagram-api-manager/compare/v1.0.77...v1.0.78
[1.0.77]: https://github.com/ScriptDevelop/instagram-api-manager/compare/v1.0.75...v1.0.77
[1.0.75]: https://github.com/ScriptDevelop/instagram-api-manager/compare/v1.0.74...v1.0.75
[1.0.74]: https://github.com/ScriptDevelop/instagram-api-manager/releases/tag/v1.0.74
