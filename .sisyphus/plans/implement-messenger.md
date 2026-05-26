# Implementar Mensajería Facebook Messenger

## TL;DR
> **Resumen**: Implementación completa de la mensajería de Facebook Messenger en el paquete. Incluye migraciones, webhook processor dedicado, servicio de envío/recepción de mensajes, broadcasting de eventos, y documentación completa en español.
> **Entregables**: 3 migraciones nuevas, 2 servicios, 1 controlador, 1 procesador webhook, 8 eventos broadcast, 2 rutas, documentación.
> **Esfuerzo**: Grande
> **Paralelo**: SÍ — 6 olas
> **Ruta Crítica**: Tarea 0 → Tarea 1 → Tareas 2,3 (paralelo) → Tareas 4,5,6,7 (paralelo) → Tarea 8

## Contexto
### Pedido Original
El usuario necesita implementar la gestión completa de mensajería de Facebook Messenger en el paquete `instagram-api-manager`. La infraestructura de autenticación OAuth para Facebook ya existe (`FacebookAccountService`, `FacebookAuthController`, modelos `FacebookPage`, `MessengerContact`, `MessengerConversation`, `MessengerMessage`), pero falta toda la capa de mensajería: webhook, envío/recepción de mensajes, broadcasting, y documentación.

### Investigación Realizada
- **Misma API base**: Instagram y Messenger usan el mismo endpoint `POST /{PAGE_ID}/messages` de la Graph API. La diferencia está en el `recipient` (PSID vs IGSID) y el parámetro obligatorio `messaging_type` en Messenger.
- **Webhook separado**: Instagram usa `"object": "instagram"`, Messenger usa `"object": "page"`. Estructura del payload es idéntica.
- **Perfil de contacto**: Messenger solo expone `name` y `profile_pic` (requiere Business Asset User Profile Access). No hay `username` ni `followers_count`.
- **Tipos de mensaje**: Messenger soporta text, image, video, audio, file, sticker, quick_reply, templates, postback, reacciones, read receipts. Adicionalmente: reply_to (respuesta a mensaje específico), múltiples imágenes (hasta 30), message tags (fuera de 24h).
- **message_echo**: Messenger incluye `app_id` y `metadata` adicional.
- **Versión API**: v25.0 (actualizado en el plan anterior).

### Decisiones de Arquitectura
- **Webhooks separados**: Instagram en `/instagram-webhook`, Messenger en `/facebook-webhook`. El usuario pidió explícitamente separarlos para facilitar depuración.
- **Procesador dedicado**: `MessengerWebhookProcessor` independiente de `BaseWebhookProcessor`.
- **Servicio dedicado**: `FacebookMessageService` renovado completamente. `MessengerMessageService` para procesamiento inbound (similar a `InstagramMessageService`).
- **Modelos reutilizados**: `MessengerContact`, `MessengerConversation`, `MessengerMessage` ya existen pero requieren migraciones para agregar campos faltantes.

## Objetivos del Trabajo
### Objetivo Central
Implementar la mensajería completa de Facebook Messenger: recepción de webhooks, procesamiento de mensajes entrantes, envío de mensajes salientes, persistencia en base de datos, broadcasting de eventos, y documentación profesional en español.

### Entregables
- [ ] 3 migraciones nuevas (campos faltantes en messenger_messages, messenger_contacts, messenger_conversations)
- [ ] `MessengerWebhookProcessor` — procesador dedicado para webhooks de Messenger
- [ ] `FacebookMessageService` completo — envío de todos los tipos de mensaje
- [ ] `MessengerMessageService` — procesamiento inbound (similar a InstagramMessageService)
- [ ] `FacebookAuthController@connect` + ruta `/facebook/connect`
- [ ] Ruta webhook `/facebook-webhook`
- [ ] 8 eventos broadcast para Messenger
- [ ] Registro de bindings en `InstagramServiceProvider`
- [ ] Documentación completa en español

### Definition of Done
```bash
# Webhook de Messenger responde a verificación GET
curl "http://localhost/facebook-webhook?hub.mode=subscribe&hub.challenge=TEST&hub.verify_token={token}"
# → 200 con "TEST"

# Envío de mensaje de texto
# → POST /facebook-webhook con payload de mensaje → guarda en BD → dispara evento broadcast

# Documentación
ls documentation/es/08-facebook-messenger.md
# → archivo existe con todas las secciones
```

### Debe Tener
- Webhook separado de Instagram (`/facebook-webhook` vs `/instagram-webhook`)
- `messaging_type` obligatorio en todos los envíos (RESPONSE, UPDATE, MESSAGE_TAG)
- Soporte para todos los tipos de mensaje: texto, imagen, audio, video, archivo, sticker, quick_reply, templates, postback, reacciones, read receipt
- Persistencia de contactos con `name` desde la API de Facebook
- Broadcasting de eventos separado de Instagram (canal `facebook-messages`)
- Documentación actualizada con ejemplos de código funcionales

### NO Debe Tener
- Mezclar webhooks de Instagram y Messenger (van separados)
- Usar `messaging_type` en Instagram (solo Messenger lo requiere)
- Duplicar lógica — reutilizar patrones de `InstagramMessageService` donde aplique
- Documentación en inglés (todo en español)
- Información ambigua o incompleta en la documentación

## Estrategia de Verificación
> CERO INTERVENCIÓN HUMANA — toda verificación es ejecutada por agentes.
- Test decision: **tests-after** — tests de integración en proyecto consumidor
- QA policy: Cada tarea tiene escenarios ejecutados por agente
- Evidencia: `.sisyphus/evidence/task-{N}-{slug}.txt`

## Estrategia de Ejecución
### Olas de Ejecución en Paralelo
Ola 0: [Fundación BD] Tarea 0 (migraciones — 3 archivos)
Ola 1: [Rutas y Auth] Tarea 1 (rutas + controller connect)
Ola 2: [Servicios Core] Tarea 2 (MessengerMessageService) + Tarea 3 (FacebookMessageService) en paralelo
Ola 3: [Webhook + Eventos] Tarea 4 (MessengerWebhookProcessor) + Tarea 5 (eventos broadcast) en paralelo
Ola 4: [ServiceProvider] Tarea 6 (registro de bindings y rutas)
Ola 5: [Documentación] Tarea 8 (documentación completa en español)
Ola 6: [Verificación] Tarea 9 (verificación y limpieza final)
Ola 7: [Instagram faltante] Tareas 10, 11, 12, 13 (4 métodos envío Instagram — independientes de Messenger)
Ola 8: [Verificación final] F1-F4

### Matriz de Dependencias
| Tarea | Puede Paralelo | Ola | Bloquea | Bloqueada Por |
|-------|---------------|-----|---------|---------------|
| 0 | NO | 0 | 2,3 | — |
| 1 | NO | 1 | 4 | 0 |
| 2 | SÍ (con 3) | 2 | 4 | 0 |
| 3 | SÍ (con 2) | 2 | 4,5 | 0 |
| 4 | SÍ (con 5) | 3 | 6 | 1,2,3 |
| 5 | SÍ (con 4) | 3 | 6 | 3 |
| 6 | NO | 4 | 7 | 4,5 |
| 7 | NO | 5 | 8,9 | 6 |
| 8 | NO | 6 | 9 | 6,7 |
| 9 | NO | 7 | 10 | 8 |
| 10 | SÍ (con 11,12,13) | 8 | — | 9 |
| 11 | SÍ (con 10,12,13) | 8 | — | 9 |
| 12 | SÍ (con 10,11,13) | 8 | — | 9 |
| 13 | SÍ (con 10,11,12) | 8 | — | 9 |

## TODOs

- [ ] 0. Crear migraciones para campos faltantes en tablas Messenger

  **Qué hacer**: Crear 3 archivos de migración en `database/migrations/`:

  **Migración 1**: `add_messenger_message_fields` — agrega a `messenger_messages`:
  ```php
  Schema::table('messenger_messages', function (Blueprint $table) {
      $table->json('attachments')->nullable()->after('json_content');
      $table->json('reactions')->nullable()->after('attachments');
      $table->string('caption')->nullable()->after('reactions');
      $table->timestamp('delivered_at')->nullable()->after('sent_at');
      $table->timestamp('created_time')->nullable()->after('failed_at');
      $table->text('message_context')->nullable()->after('message_content');
      $table->string('message_context_id')->nullable()->after('message_context');
      $table->string('quick_reply_payload', 1000)->nullable()->after('message_context_id');
      $table->string('postback_payload', 1000)->nullable()->after('quick_reply_payload');
      $table->string('template_payload', 1000)->nullable()->after('postback_payload');
  });
  ```

  **Migración 2**: `add_messenger_contact_fields` — agrega a `messenger_contacts`:
  ```php
  Schema::table('messenger_contacts', function (Blueprint $table) {
      $table->string('name')->nullable()->after('messenger_user_id');
      $table->timestamp('last_interaction_at')->nullable()->after('profile_picture');
      $table->timestamp('profile_synced_at')->nullable()->after('last_interaction_at');
  });
  ```

  **Migración 3**: `add_messenger_conversation_fields` — agrega a `messenger_conversations`:
  ```php
  Schema::table('messenger_conversations', function (Blueprint $table) {
      $table->integer('unread_count')->default(0)->after('last_message_at');
      $table->boolean('is_archived')->default(false)->after('unread_count');
      $table->timestamp('updated_time')->nullable()->after('last_referral');
  });
  ```

  Usar nombres de archivo con timestamp actual: `date('Y_m_d_His')_descripcion.php`.

  **Adicional — archivos multimedia**: Agregar sección `media` a `config/facebook.php` y por consistencia a `config/instagram.php`:
  ```php
  // config/facebook.php
  'media' => [
      'disk' => env('FACEBOOK_MEDIA_DISK', 'public'),
      'path' => env('FACEBOOK_MEDIA_PATH', 'facebook'),
      'max_size' => env('FACEBOOK_MEDIA_MAX_SIZE', 25600), // KB
  ],
  
  // config/instagram.php
  'media' => [
      'disk' => env('INSTAGRAM_MEDIA_DISK', 'public'),
      'path' => env('INSTAGRAM_MEDIA_PATH', 'instagram'),
      'max_size' => env('INSTAGRAM_MEDIA_MAX_SIZE', 25600), // KB
  ],
  ```
  Esto permite al usuario personalizar rutas separadas para cada plataforma. Siguiendo el patrón de WhatsApp (`storage/app/public/whatsapp/{audios,documents,images,stickers,videos}/`), los archivos se almacenarán en:
  ```
  storage/app/public/instagram/   ← archivos multimedia de Instagram
  storage/app/public/facebook/    ← archivos multimedia de Messenger
  ```

  **Adicional — Broadcast (Laravel Reverb)**: Messenger necesita su propia configuración de broadcast, independiente de Instagram. Agregar sección `broadcast` a `config/facebook.php`:
  ```php
  'broadcast' => [
      'channel_type' => env('FACEBOOK_BROADCAST_CHANNEL_TYPE', 'public'), // 'public' o 'private'
  ],
  ```
  Y modificar `src/routes/channels.php` para registrar el canal privado de Messenger:
  ```php
  // Al final del archivo, agregar:
  if (config('facebook.broadcast.channel_type') === 'private') {
      Broadcast::channel('facebook-messages', function ($user) {
          return $user !== null;
      });
  }
  ```
  Esto permite que cuando el proyecto consumidor use Laravel Reverb, los eventos de Messenger se transmitan por el canal `facebook-messages` (público o privado según configuración), igual que Instagram ya lo hace con `instagram-messages`.

  **NO debe hacer**:
  - NO usar `hasColumn` — estas migraciones son nuevas, las tablas están recién creadas sin esos campos
  - NO modificar migraciones existentes
  - NO eliminar columnas existentes

  **Perfil de Agente Recomendado**:
  - Categoría: `quick`
  - Skills: [`laravel-patterns`]

  **Paralelización**: Puede Paralelo: NO | Ola 0 | Bloquea: [2,3] | Bloqueada Por: —

  **Referencias**:
  - Modelo: `src/Models/MessengerMessage.php` — fillable actual para confirmar campos
  - Modelo: `src/Models/MessengerContact.php` — fillable actual
  - Modelo: `src/Models/MessengerConversation.php` — fillable actual
  - Migración Instagram referencia: `database/migrations/2024_12_17_000030_create_instagram_messages_table.php` — campos de attachments, reactions, etc.

  **Criterios de Aceptación**:
  - [ ] 3 archivos de migración creados en `database/migrations/`
  - [ ] `messenger_messages` tiene: attachments, reactions, caption, delivered_at, created_time, message_context, message_context_id, quick_reply_payload, postback_payload, template_payload
  - [ ] `messenger_contacts` tiene: name, last_interaction_at, profile_synced_at
  - [ ] `messenger_conversations` tiene: unread_count, is_archived, updated_time
  - [ ] `config/facebook.php` tiene sección `media` con `disk`, `path`, `max_size`
  - [ ] `config/instagram.php` tiene sección `media` con `disk`, `path`, `max_size`
  - [ ] `config/facebook.php` tiene sección `broadcast` con `channel_type`
  - [ ] `src/routes/channels.php` tiene canal `facebook-messages` para Reverb

  **QA Scenarios**:
  ```
  Escenario: Archivos de migración existen
    Herramienta: Bash
    Pasos: ls database/migrations/*messenger*message_fields* database/migrations/*messenger*contact_fields* database/migrations/*messenger*conversation_fields*
    Esperado: 3 archivos listados sin error
    Evidencia: .sisyphus/evidence/task-0-migrations-exist.txt

  Escenario: Migraciones declaran las columnas correctas
    Herramienta: Bash
    Pasos: grep -c "attachments\|reactions\|delivered_at\|created_time" database/migrations/*messenger*message_fields*.php
    Esperado: 4 coincidencias (todas las columnas nuevas)
    Evidencia: .sisyphus/evidence/task-0-columns.txt
  ```

  **Commit**: SÍ | Mensaje: `feat(messenger): agregar campos faltantes a tablas Messenger + configuración de archivos multimedia` | Archivos: [`database/migrations/{timestamp}_add_messenger_message_fields.php`, `database/migrations/{timestamp}_add_messenger_contact_fields.php`, `database/migrations/{timestamp}_add_messenger_conversation_fields.php`, `config/facebook.php`, `config/instagram.php`]

- [ ] 1. Agregar ruta `/facebook/connect` y método `connect()` en FacebookAuthController

  **Qué hacer**:
  1. Modificar `src/Http/Controllers/Auth/FacebookAuthController.php`:
     - Agregar método `connect()` que obtenga `FacebookAccountService` y llame a `getAuthorizationUrl()` para redirigir al usuario a Facebook OAuth.
     ```php
     public function connect()
     {
         $facebookAccountService = app(FacebookAccountService::class);
         $authUrl = $facebookAccountService->getAuthorizationUrl();
         return redirect($authUrl);
     }
     ```
  2. Modificar `routes/instagram_callback.php`:
     - Agregar ruta `Route::get('/facebook/connect', [FacebookAuthController::class, 'connect'])->name('facebook.connect');`

  **NO debe hacer**:
  - NO modificar la lógica de `getAuthorizationUrl()` — ya existe y funciona
  - NO cambiar los permisos (scopes) existentes

  **Perfil de Agente Recomendado**:
  - Categoría: `quick`
  - Skills: [`laravel-patterns`]

  **Paralelización**: Puede Paralelo: NO | Ola 1 | Bloquea: [4] | Bloqueada Por: [0]

  **Referencias**:
  - Controller: `src/Http/Controllers/Auth/FacebookAuthController.php` — líneas 1-51
  - Servicio: `src/Services/FacebookAccountService.php` — método `getAuthorizationUrl()` líneas 24-38
  - Ruta Instagram referencia: `routes/instagram_callback.php` línea 10: `Route::get('/instagram/connect', ...)`
  - Patrón a seguir: `InstagramAuthController@connect` en `src/Http/Controllers/Auth/InstagramAuthController.php` línea 126

  **Criterios de Aceptación**:
  - [ ] `FacebookAuthController` tiene método `connect()`
  - [ ] `routes/instagram_callback.php` tiene `Route::get('/facebook/connect', ...)`
  - [ ] La ruta tiene nombre `facebook.connect`

  **QA Scenarios**:
  ```
  Escenario: Método connect existe en el controller
    Herramienta: Bash
    Pasos: grep -A 5 "function connect" src/Http/Controllers/Auth/FacebookAuthController.php
    Esperado: Método encontrado que llama a getAuthorizationUrl() y retorna redirect()
    Evidencia: .sisyphus/evidence/task-1-connect.txt

  Escenario: Ruta registrada
    Herramienta: Bash
    Pasos: grep "facebook/connect" routes/instagram_callback.php
    Esperado: Línea con Route::get('/facebook/connect', ...)
    Evidencia: .sisyphus/evidence/task-1-route.txt
  ```

  **Commit**: SÍ | Mensaje: `feat(messenger): agregar ruta /facebook/connect y método connect()` | Archivos: [`src/Http/Controllers/Auth/FacebookAuthController.php`, `routes/instagram_callback.php`]

- [ ] 2. Crear MessengerMessageService — procesamiento inbound de mensajes

  **Qué hacer**: Crear `src/Services/MessengerMessageService.php` con la misma estructura que `InstagramMessageService` pero adaptado a Messenger:

  1. **Propiedades**: `ApiClient $apiClient`, `?string $pageAccessToken`, `?string $pageId`, `FacebookAccountService $accountService`
  2. **Constructor**: inicializar `$this->apiClient` vía `app(ApiClient::class)->withBaseUrl(config('facebook.api.base_url'))->withVersion(config('facebook.api.version'))`
  3. **Métodos de configuración**: `withPageAccessToken(string $token): self`, `withPageId(string $pageId): self`
  4. **`processWebhookMessage(array $messaging): array`** — entry point, igual que Instagram
  5. **`processMessage(array $messageData): array`** — lógica principal adaptada:
     - Extraer contexto: `$senderId = $messaging['sender']['id']` (PSID), `$recipientId = $messaging['recipient']['id']` (PAGE_ID)
     - Buscar página: `FacebookPage::where('page_id', $pageId)->first()`
     - `findOrCreateConversation(page_id, messenger_user_id)`
     - `handleEventByType()` → dispatch a processIncomingMessage, processPostback, processReaction, processRead, processReferral, processMessageEdit, processOptin
  6. **`processIncomingMessage()`** — guarda en `messenger_messages` con `message_method = 'incoming'`, `message_type` según contenido (text, image, video, audio, file, sticker, quick_reply, template)
  7. **`handleEventByType()`** — mismo switch que Instagram: message, postback, reaction, read, referral, message_edit, optin
  8. **`updateOrCreateContact()`** — usa `GET /{PSID}?fields=name,profile_pic` para obtener perfil
  9. **`dispatchBroadcastEvent()`** — dispara eventos Messenger (creados en Task 5)
  10. **`findOrCreateConversation()`** — busca/crea en `messenger_conversations`
  11. **`processAttachments()`** — descarga archivos multimedia a `storage/app/public/facebook/` usando la configuración `config('facebook.media.path')`. Guarda metadata en `messenger_messages.attachments` (JSON) y la ruta local en la tabla `instagram_media_messages` (o crear tabla equivalente para Messenger)

  **Estructura a seguir**: Copiar la estructura de `InstagramMessageService` (1185 líneas) y adaptar nombres de modelos/tablas:
  - `InstagramModelResolver::instagram_message()` → `MessengerMessage::query()`
  - `InstagramModelResolver::instagram_conversation()` → `MessengerConversation::query()`
  - `InstagramModelResolver::instagram_contact()` → `MessengerContact::query()`
  - `instagram_business_account_id` → `page_id`
  - `instagram_user_id` → `messenger_user_id`

  **NO debe hacer**:
  - NO copiar métodos de envío de mensajes (eso va en FacebookMessageService)
  - NO usar `InstagramModelResolver` — usar los modelos Messenger directamente
  - NO incluir lógica de `is_echo` con `findBusinessAccount()` — en Messenger el echo incluye `app_id` para identificar la app

  **Perfil de Agente Recomendado**:
  - Categoría: `deep` — archivo grande, lógica compleja
  - Skills: [`laravel-patterns`]

  **Paralelización**: Puede Paralelo: SÍ (con 3) | Ola 2 | Bloquea: [4] | Bloqueada Por: [0]

  **Referencias**:
  - Modelo a seguir: `src/Services/InstagramMessageService.php` — archivo completo (1185 líneas)
  - Modelos Messenger: `src/Models/MessengerMessage.php`, `src/Models/MessengerConversation.php`, `src/Models/MessengerContact.php`, `src/Models/FacebookPage.php`
  - Webhook payload Messenger: `object: "page"`, `entry[].messaging[]`, `sender.id` (PSID), `recipient.id` (PAGE_ID)

  **Criterios de Aceptación**:
  - [ ] Archivo `src/Services/MessengerMessageService.php` creado
  - [ ] Implementa `processWebhookMessage()`, `processMessage()`, `handleEventByType()`
  - [ ] Implementa `processIncomingMessage()`, `processPostback()`, `processReaction()`, `processRead()`, `processReferral()`, `processMessageEdit()`, `processOptin()`
  - [ ] Implementa `updateOrCreateContact()` con `GET /{PSID}?fields=name,profile_pic`
  - [ ] Implementa `dispatchBroadcastEvent()` con eventos de Messenger
  - [ ] Usa modelos Messenger directamente (no InstagramModelResolver)

  **QA Scenarios**:
  ```
  Escenario: Archivo creado con métodos clave
    Herramienta: Bash
    Pasos: grep -c "function processWebhookMessage\|function processMessage\|function handleEventByType\|function processIncomingMessage\|function updateOrCreateContact" src/Services/MessengerMessageService.php
    Esperado: 5 coincidencias (todos los métodos)
    Evidencia: .sisyphus/evidence/task-2-methods.txt

  Escenario: No usa InstagramModelResolver
    Herramienta: Bash
    Pasos: grep -c "InstagramModelResolver" src/Services/MessengerMessageService.php
    Esperado: 0 coincidencias
    Evidencia: .sisyphus/evidence/task-2-no-instagram.txt
  ```

  **Commit**: SÍ | Mensaje: `feat(messenger): crear MessengerMessageService para procesamiento inbound` | Archivos: [`src/Services/MessengerMessageService.php`]

- [ ] 3. Reconstruir FacebookMessageService — envío de mensajes

  **Qué hacer**: Reescribir completamente `src/Services/FacebookMessageService.php` (actualmente esqueleto de 24 líneas) con soporte completo de envío:

  1. **Propiedades**: `ApiClient $apiClient`, `?string $pageAccessToken`, `?string $pageId`
  2. **Constructor**: `$this->apiClient = app(ApiClient::class)->withBaseUrl(config('facebook.api.base_url'))->withVersion(config('facebook.api.version'))`
  3. **Métodos fluentes**: `withPageAccessToken(string $token): self`, `withPageId(string $id): self`
  4. **`sendMessageGeneric()`** — método central que construye el payload, crea registro en BD con `status = 'pending'`, envía a `POST /{PAGE_ID}/messages`, actualiza registro:
     - **Diferencia clave con Instagram**: incluir `"messaging_type"` en el payload
  5. **Métodos de envío** (todos llaman a `sendMessageGeneric`):
     - `sendTextMessage(string $recipientId, string $text, string $messagingType = 'RESPONSE')` 
     - `sendImageMessage(string $recipientId, string $imageUrl, string $messagingType = 'RESPONSE')`
     - `sendAudioMessage(string $recipientId, string $audioUrl, string $messagingType = 'RESPONSE')`
     - `sendVideoMessage(string $recipientId, string $videoUrl, string $messagingType = 'RESPONSE')`
     - `sendFileMessage(string $recipientId, string $fileUrl, string $messagingType = 'RESPONSE')`
     - `sendStickerMessage(string $recipientId)` — `attachment.type = "like_heart"` (Facebook) o tipo específico
     - `sendQuickReplies(string $recipientId, string $text, array $quickReplies, string $messagingType = 'RESPONSE')`
     - `sendGenericTemplate(string $recipientId, array $elements, string $messagingType = 'RESPONSE')`
     - `sendButtonTemplate(string $recipientId, string $text, array $buttons, string $messagingType = 'RESPONSE')`
     - `sendReadReceipt(string $recipientId)` — `sender_action: mark_seen`
     - **`sendReaction(string $recipientId, string $messageId, string $reaction)`** — `sender_action: react` + `payload.message_id` + `reaction` (emoji UTF-8 o 😊/🎉/etc.)
     - **`sendReply(string $recipientId, string $messageId, array $messagePayload, string $messagingType = 'RESPONSE')`** — agrega `reply_to: {mid: $messageId}` al payload. Funciona con cualquier tipo de mensaje (texto, media, template).
     - **`sendMultipleImages(string $recipientId, array $imageUrls, string $messagingType = 'RESPONSE')`** — array de attachments con `type: image`, hasta 30 imágenes (Instagram: hasta 10).
     - **`uploadAttachment(string $url, string $type)`** — sube media a `POST /{PAGE_ID}/message_attachments` con `is_reusable: true`, retorna `attachment_id` para reutilizar en múltiples mensajes.
  6. **`sendTaggedMessage()`** — para mensajes fuera de la ventana de 24h, requiere `tag` parameter

  **Payload ejemplo** (texto):
  ```json
  {
    "recipient": {"id": "{PSID}"},
    "messaging_type": "RESPONSE",
    "message": {"text": "Hola!"}
  }
  ```

  **Payload ejemplo** (imagen):
  ```json
  {
    "recipient": {"id": "{PSID}"},
    "messaging_type": "RESPONSE",
    "message": {
      "attachment": {
        "type": "image",
        "payload": {"url": "https://..."}
      }
    }
  }
  ```

  **NO debe hacer**:
  - NO omitir `messaging_type` en ningún envío
  - NO usar `$this->instagramUserId` (Messenger usa `$this->pageId`)
  - NO incluir `validateCredentials()` con Instagram-specific checks

  **Perfil de Agente Recomendado**:
  - Categoría: `deep` — reescritura completa de servicio
  - Skills: [`laravel-patterns`]

  **Paralelización**: Puede Paralelo: SÍ (con 2) | Ola 2 | Bloquea: [5] | Bloqueada Por: [0]

  **Referencias**:
  - Archivo existente: `src/Services/FacebookMessageService.php` — esqueleto actual (24 líneas)
  - Modelo a seguir: `src/Services/InstagramMessageService.php` — métodos `sendMessageGeneric()`, `sendTextMessage()`, etc.
  - API endpoint: `POST /v25.0/{PAGE_ID}/messages?access_token={PAGE_TOKEN}`

  **Criterios de Aceptación**:
  - [ ] Constructor usa `app(ApiClient::class)` con config de Facebook
  - [ ] `sendMessageGeneric()` incluye `messaging_type` en el payload
  - [ ] Metodos: sendTextMessage, sendImageMessage, sendAudioMessage, sendVideoMessage, sendFileMessage, sendStickerMessage, sendQuickReplies, sendGenericTemplate, sendButtonTemplate, sendReadReceipt, sendTaggedMessage
  - [ ] Cada método llama a `sendMessageGeneric()` con el `messageType` correcto
  - [ ] Los mensajes se guardan en `messenger_messages` con `message_method = 'outgoing'`
  - [ ] La conversación se actualiza con `last_message_at`

  **QA Scenarios**:
  ```
  Escenario: Métodos de envío declarados
    Herramienta: Bash
    Pasos: grep -c "function send" src/Services/FacebookMessageService.php
    Esperado: Al menos 14 coincidencias (todos los métodos send* + uploadAttachment)
    Evidencia: .sisyphus/evidence/task-3-send-methods.txt

  Escenario: messaging_type presente en payload
    Herramienta: Bash
    Pasos: grep -c "messaging_type" src/Services/FacebookMessageService.php
    Esperado: Al menos 1 coincidencia (en sendMessageGeneric)
    Evidencia: .sisyphus/evidence/task-3-messaging-type.txt
  ```

  **Commit**: SÍ | Mensaje: `feat(messenger): reconstruir FacebookMessageService con soporte completo de envío` | Archivos: [`src/Services/FacebookMessageService.php`]

- [ ] 4. Crear MessengerWebhookProcessor — procesador dedicado para webhooks de Messenger

  **Qué hacer**: Crear `src/Services/WebhookProcessors/MessengerWebhookProcessor.php`:
  1. Implementar `WebhookProcessorInterface` (mismo contrato que `BaseWebhookProcessor`)
  2. **`handle(Request $request)`** — detecta GET (verificación) vs POST (procesamiento)
  3. **`verifyWebhook(Request $request)`** — valida `hub_verify_token` contra `config('facebook.webhook.verify_token')`, retorna `hub_challenge`
  4. **`processWebhookPayload(Request $request)`** — itera `entry[] → messaging[]`, por cada messaging:
     - Extrae `page_id` desde `entry['id']` o `messaging['recipient']['id']`
     - Busca `FacebookPage` por `page_id` para obtener el `access_token`
     - Instancia `MessengerMessageService`, configura `withPageAccessToken()` + `withPageId()`
     - Llama a `$messengerService->processWebhookMessage($messaging)`
  5. **`determineMessageType()`** — igual que `BaseWebhookProcessor`
  6. **Logging**: usar `Log::channel('facebook')`

  **NO debe hacer**:
  - NO usar `InstagramMessageService`
  - NO reutilizar `BaseWebhookProcessor` (webhooks separados, como pidió el usuario)
  - NO ignorar `message_echoes` — procesarlos igual que Instagram para guardar `delivered_at`

  **Perfil de Agente Recomendado**:
  - Categoría: `quick` — archivo nuevo, patrón ya definido
  - Skills: [`laravel-patterns`]

  **Paralelización**: Puede Paralelo: SÍ (con 5) | Ola 3 | Bloquea: [6] | Bloqueada Por: [1,2,3]

  **Referencias**:
  - Interfaz: `src/Contracts/WebhookProcessorInterface.php` — 3 métodos
  - Modelo a seguir: `src/Services/WebhookProcessors/BaseWebhookProcessor.php` — estructura completa (139 líneas)
  - Modelo: `src/Models/FacebookPage.php` — para buscar page por page_id

  **Criterios de Aceptación**:
  - [ ] Implementa `WebhookProcessorInterface`
  - [ ] `verifyWebhook()` usa `config('facebook.webhook.verify_token')`
  - [ ] `processWebhookPayload()` busca `FacebookPage` por `page_id` y configura `MessengerMessageService`
  - [ ] Usa `Log::channel('facebook')` para logging

  **QA Scenarios**:
  ```
  Escenario: Interfaz implementada
    Herramienta: Bash
    Pasos: grep "implements WebhookProcessorInterface" src/Services/WebhookProcessors/MessengerWebhookProcessor.php
    Esperado: 1 coincidencia
    Evidencia: .sisyphus/evidence/task-4-interface.txt

  Escenario: Usa config de Facebook para verify_token
    Herramienta: Bash
    Pasos: grep "facebook.webhook.verify_token" src/Services/WebhookProcessors/MessengerWebhookProcessor.php
    Esperado: 1 coincidencia
    Evidencia: .sisyphus/evidence/task-4-config.txt
  ```

  **Commit**: SÍ | Mensaje: `feat(messenger): crear MessengerWebhookProcessor dedicado` | Archivos: [`src/Services/WebhookProcessors/MessengerWebhookProcessor.php`]

- [ ] 5. Crear eventos broadcast para Messenger

  **Qué hacer**: Crear 8 archivos de eventos en `src/Events/`, uno por cada tipo:
  1. `MessengerMessageReceived.php` — mensaje entrante
  2. `MessengerMessageEchoReceived.php` — echo de mensaje saliente
  3. `MessengerPostbackReceived.php` — postback
  4. `MessengerReactionReceived.php` — reacción
  5. `MessengerReadReceived.php` — read receipt
  6. `MessengerReferralReceived.php` — referral
  7. `MessengerMessageEdited.php` — edición
  8. `MessengerOptinReceived.php` — opt-in

  **Estructura de cada evento** (idéntica a Instagram pero con nombre y canal distintos):
  ```php
  class MessengerMessageReceived implements ShouldBroadcastNow
  {
      use Dispatchable, InteractsWithSockets;
      public array $data;
      public function __construct(array $data) { $this->data = $data; }
      public function broadcastOn(): Channel {
          $channelName = 'facebook-messages';  // ← canal separado de Instagram
          return config('facebook.broadcast.channel_type') === 'private'
              ? new PrivateChannel($channelName)
              : new Channel($channelName);
      }
      public function broadcastAs(): string { return 'MessengerMessageReceived'; }
  }
  ```

  **NO debe hacer**:
  - NO usar el canal `instagram-messages` — Messenger usa `facebook-messages`
  - NO usar `config('instagram.broadcast.channel_type')` — usar `config('facebook.broadcast.channel_type')`
  - NO modificar eventos de Instagram existentes

  **Perfil de Agente Recomendado**:
  - Categoría: `quick` — archivos pequeños, patrón repetitivo
  - Skills: [`laravel-patterns`]

  **Paralelización**: Puede Paralelo: SÍ (con 4) | Ola 3 | Bloquea: [6] | Bloqueada Por: [3]

  **Referencias**:
  - Modelo: `src/Events/InstagramMessageReceived.php` — estructura de evento Instagram
  - Modelo: `src/Events/InstagramMessageEchoReceived.php` — estructura de evento echo

  **Criterios de Aceptación**:
  - [ ] 8 archivos creados en `src/Events/`
  - [ ] Todos usan canal `facebook-messages`
  - [ ] Todos implementan `ShouldBroadcastNow`
  - [ ] Todos tienen `broadcastAs()` con nombre único

  **QA Scenarios**:
  ```
  Escenario: 8 eventos creados
    Herramienta: Bash
    Pasos: ls src/Events/Messenger*.php | wc -l
    Esperado: 8
    Evidencia: .sisyphus/evidence/task-5-count.txt

  Escenario: Canal facebook-messages
    Herramienta: Bash
    Pasos: grep -c "facebook-messages" src/Events/Messenger*.php
    Esperado: 8 coincidencias
    Evidencia: .sisyphus/evidence/task-5-channel.txt
  ```

  **Commit**: SÍ | Mensaje: `feat(messenger): crear 8 eventos broadcast para Messenger` | Archivos: [`src/Events/MessengerMessageReceived.php`, `...EchoReceived.php`, `...PostbackReceived.php`, `...ReactionReceived.php`, `...ReadReceived.php`, `...ReferralReceived.php`, `...MessageEdited.php`, `...OptinReceived.php`]

- [ ] 6. Registrar bindings, rutas y eventos en InstagramServiceProvider

  **Qué hacer**: Modificar `src/Providers/InstagramServiceProvider.php`:

  1. **Agregar imports** al inicio del archivo para los nuevos servicios, eventos, y procesador
  2. **En `register()`** — agregar bindings:
     ```php
     $this->app->singleton('facebook.message', function ($app) {
         return new FacebookMessageService();
     });
     $this->app->singleton('messenger.message', function ($app) {
         return new MessengerMessageService();
     });
     ```
  3. **En `boot()`** — después de las rutas de Instagram, agregar ruta del webhook de Messenger:
     ```php
     // Registrar ruta del webhook de Facebook Messenger
     Route::prefix('facebook-webhook')->group(function () {
         Route::match(['get', 'post'], '/', [MessengerWebhookController::class, 'handle'])
             ->name('facebook.webhook.handle');
     });
     ```
  4. **Actualizar binding de Facade `facebook`** para incluir el nuevo servicio de mensajería:
     ```php
     $this->app->singleton('facebook', function () {
         return new class {
             public function account() { return app('facebook.account'); }
             public function message() { return app('facebook.message'); }  // ← ya existe, ahora funciona
         };
     });
     ```
  5. **Eliminar el binding viejo** de `facebook.message` si existe (estaba apuntando a un esqueleto vacío)

  **NO debe hacer**:
  - NO eliminar bindings de Instagram
  - NO mover rutas existentes
  - NO duplicar el binding `facebook.message`

  **Perfil de Agente Recomendado**:
  - Categoría: `quick`
  - Skills: [`laravel-patterns`]

  **Paralelización**: Puede Paralelo: NO | Ola 4 | Bloquea: [7] | Bloqueada Por: [4,5]

  **Referencias**:
  - Archivo: `src/Providers/InstagramServiceProvider.php` — líneas 42-48 (bindings de facebook.account y facebook.message), líneas 123-130 (rutas de Instagram webhook)
  - Controller a crear: `MessengerWebhookController` (se crea inline o como parte de esta tarea)

  **Criterios de Aceptación**:
  - [ ] `FacebookMessageService` está registrado como `facebook.message` (actualizado)
  - [ ] `MessengerMessageService` está registrado como `messenger.message`
  - [ ] Ruta `facebook-webhook` está registrada en `boot()`
  - [ ] Facade `Facebook` tiene método `message()` que devuelve el servicio real

  **QA Scenarios**:
  ```
  Escenario: Binding de messenger.message existe
    Herramienta: Bash
    Pasos: grep "messenger.message" src/Providers/InstagramServiceProvider.php
    Esperado: Al menos 1 coincidencia
    Evidencia: .sisyphus/evidence/task-6-bindings.txt

  Escenario: Ruta facebook-webhook registrada
    Herramienta: Bash
    Pasos: grep "facebook-webhook" src/Providers/InstagramServiceProvider.php
    Esperado: 1 coincidencia
    Evidencia: .sisyphus/evidence/task-6-route.txt
  ```

  **Commit**: SÍ | Mensaje: `feat(messenger): registrar bindings, rutas y eventos de Messenger en ServiceProvider` | Archivos: [`src/Providers/InstagramServiceProvider.php`]

- [ ] 7. Crear MessengerWebhookController

  **Qué hacer**: Crear `src/Http/Controllers/MessengerWebhookController.php`:
  ```php
  class MessengerWebhookController extends Controller
  {
      public function handle(Request $request)
      {
          $processor = new MessengerWebhookProcessor(
              app(MessengerMessageService::class)
          );
          return $processor->handle($request);
      }
  }
  ```

  **Perfil de Agente Recomendado**:
  - Categoría: `quick`
  - Skills: [`laravel-patterns`]

  **Paralelización**: Puede Paralelo: NO | Ola 4 | Bloquea: [7] | Bloqueada Por: [4,5]

  **Referencias**:
  - Modelo: `src/Http/Controllers/InstagramWebhookController.php` — estructura idéntica

  **Criterios de Aceptación**:
  - [ ] Archivo `src/Http/Controllers/MessengerWebhookController.php` creado
  - [ ] `handle()` instancia `MessengerWebhookProcessor` con `MessengerMessageService`

  **QA Scenarios**:
  ```
  Escenario: Controller creado
    Herramienta: Bash
    Pasos: grep "class MessengerWebhookController" src/Http/Controllers/MessengerWebhookController.php
    Esperado: 1 coincidencia
    Evidencia: .sisyphus/evidence/task-7-controller.txt
  ```

  **Commit**: SÍ | Mensaje: `feat(messenger): crear MessengerWebhookController` | Archivos: [`src/Http/Controllers/MessengerWebhookController.php`]

- [ ] 8. Reorganizar documentación y crear docs de Messenger

  **Qué hacer**: Reorganizar `documentation/es/` en subcarpetas por plataforma:

  **Paso 1 — Mover archivos existentes** de Instagram a subcarpeta:
  ```
  documentation/es/
  ├── 01-instalacion.md          ← se queda (compartido)
  ├── 02-configuracion.md        ← se queda (compartido)
  │
  ├── instagram/                 ← NUEVA carpeta
  │   ├── 03-cuentas.md          ← renombrado de 03-cuentas.md
  │   ├── 04-mensajes.md         ← renombrado de 04-mensajes.md
  │   ├── 05-menu-persistente.md ← renombrado de 05-menu-persistente.md
  │   ├── 06-enlaces.md          ← renombrado de 06-enlaces.md
  │   └── 07-webhooks.md         ← renombrado de 07-webhooks.md
  │
  └── messenger/                 ← NUEVA carpeta
      ├── 01-autenticacion.md    ← autenticación y conexión de páginas
      ├── 02-mensajes.md         ← envío/recepción todos los tipos
      ├── 03-webhooks.md         ← webhook Messenger
      └── 04-eventos.md          ← broadcasting Laravel Reverb
  ```

  **Paso 2 — Crear `messenger/01-autenticacion.md`**:
  - Flujo OAuth completo: `/facebook/connect` → permisos → callback → páginas guardadas
  - Variables `.env` de Facebook
  - Registro manual de página (sin OAuth)
  - Ejemplo Blade para botón "Conectar Facebook"
  - Ejemplo de código: `Facebook::account()->getAuthorizationUrl()`

  **Paso 3 — Crear `messenger/02-mensajes.md`**:
  - TODOS los tipos de mensaje soportados, cada uno con:
    - Ejemplo de código funcional completo
    - Payload JSON que se envía a la API
    - Respuesta esperada de la API
    - Notas sobre restricciones (24h, tamaño, formatos)
  - Tipos: texto, imagen, audio, video, archivo, sticker, quick_replies, templates (generic, button), reaction, reply, múltiples imágenes, upload attachment, read receipt, tagged messages
  - Explicación de `messaging_type` (RESPONSE, UPDATE, MESSAGE_TAG)
  - Tabla comparativa: tipos de mensaje Instagram vs Messenger

  **Paso 4 — Crear `messenger/03-webhooks.md`**:
  - URL: `/facebook-webhook`
  - Verificación GET con `hub.verify_token`
  - Payload entrante con ejemplos JSON reales para cada tipo de evento
  - Diferencia con Instagram: `"object": "page"` vs `"object": "instagram"`
  - Configuración en Meta Dashboard (qué eventos suscribir)

  **Paso 5 — Crear `messenger/04-eventos.md`**:
  - 8 eventos broadcast en canal `facebook-messages`
  - Payload de cada evento
  - Configuración Reverb: `FACEBOOK_BROADCAST_CHANNEL_TYPE`
  - Ejemplo listener Laravel
  - Ejemplo suscripción frontend con Laravel Echo

  **Paso 6 — Actualizar `README.md`**:
  - Nueva tabla de contenidos con subcarpetas

  **Estilo obligatorio**:
  - Español profesional, sin anglicismos innecesarios
  - Tablas para comparaciones y referencias rápidas
  - Bloques de código con sintaxis highlight
  - Callouts: ⚠️ advertencias, 💡 tips, ❌ errores comunes
  - Cada ejemplo de código debe ser completo y funcional
  - Comentarios explicativos en el código

  **NO debe hacer**:
  - NO usar inglés
  - NO ejemplos rotos o incompletos
  - NO asumir conocimiento previo de Instagram
  - NO ambigüedades — cada concepto debe quedar 100% claro

  **Perfil de Agente**: `writing` | **Skills**: []
  **Paralelización**: NO | Ola 5 | Bloquea: [9] | Bloqueada Por: [6,7]

  **Criterios de Aceptación**:
  - [ ] Archivos de Instagram movidos a `documentation/es/instagram/`
  - [ ] 4 archivos creados en `documentation/es/messenger/`
  - [ ] Cada archivo Messenger tiene ejemplos de código funcionales (mínimo 3 por archivo)
  - [ ] `README.md` actualizado con nueva tabla de contenidos
  - [ ] Documento en español profesional sin errores

  **QA Scenarios**:
  ```
  Escenario: Carpetas creadas
    Herramienta: Bash
    Pasos: ls documentation/es/instagram/ documentation/es/messenger/
    Esperado: 5 archivos en instagram/, 4 en messenger/
    Evidencia: .sisyphus/evidence/task-8-folders.txt

  Escenario: README actualizado
    Herramienta: Bash
    Pasos: grep "messenger/" README.md | head -5
    Esperado: Entradas de Messenger en tabla de contenidos
    Evidencia: .sisyphus/evidence/task-8-readme.txt
  ```

  **Commit**: SÍ | Mensaje: `docs: reorganizar documentación por plataforma + docs completos de Messenger` | Archivos: [`documentation/es/instagram/*`, `documentation/es/messenger/*`, `README.md`]

- [ ] 9. Verificación final, limpieza y actualización del wizard de instalación

  **Qué hacer**:
  1. Verificar que todos los archivos nuevos existen
  2. Verificar que `MessengerWebhookController` está importado en `InstagramServiceProvider`
  3. Verificar que `FacebookMessageService` viejo fue reemplazado (no quedó el esqueleto)
  4. Verificar que `MessengerMessageService` no referencia `InstagramModelResolver`
  5. Verificar que los eventos usan canal `facebook-messages` y config `facebook.broadcast`
  6. Actualizar CHANGELOG.md con entrada de la versión
  7. Si existe `config/instagram.php`, verificar que los modelos Messenger están registrados en la sección `models`
  8. **Actualizar wizard de instalación** (`src/Console/Commands/InstallInstagramApiManager.php`):
     - Agregar CSRF exclusion para `facebook-webhook/*` y `facebook/connect` (mismo método `addCsrfExclusion()`)
     - Agregar variables `.env` de Messenger al output final:
       ```
       # Facebook OAuth
       FACEBOOK_CLIENT_ID=<tu_facebook_client_id>
       FACEBOOK_CLIENT_SECRET=<tu_facebook_client_secret>
       FACEBOOK_REDIRECT_URI=https://tu-dominio.com/facebook/callback
       
       # Facebook API
       FACEBOOK_API_BASE_URL=https://graph.facebook.com
       FACEBOOK_API_VERSION=v25.0
       FACEBOOK_API_TIMEOUT=30
       
       # Facebook Webhook
       FACEBOOK_WEBHOOK_VERIFY_TOKEN=<tu_token_secreto>
       
       # Facebook Broadcast (Laravel Reverb)
       FACEBOOK_BROADCAST_CHANNEL_TYPE=public
       
       # Facebook Media
       FACEBOOK_MEDIA_DISK=public
       FACEBOOK_MEDIA_PATH=facebook
       ```
     - Corregir `INSTAGRAM_API_VERSION=v23.0` → `v25.0` en el output existente (línea 182)

  **NO debe hacer**:
  - NO modificar lógica de negocio
  - NO agregar nuevas funcionalidades

  **Perfil de Agente Recomendado**:
  - Categoría: `quick`
  - Skills: []

  **Paralelización**: Puede Paralelo: NO | Ola 6 | Bloquea: [] | Bloqueada Por: [8]

  **Criterios de Aceptación**:
  - [ ] Todos los archivos de las tareas 0-8 existen
  - [ ] ServiceProvider tiene todos los imports necesarios
  - [ ] CHANGELOG actualizado
  - [ ] Sin errores de linter en archivos nuevos

  **QA Scenarios**:
  ```
  Escenario: Sin referencias a InstagramModelResolver en Messenger
    Herramienta: Bash
    Pasos: grep -r "InstagramModelResolver" src/Services/MessengerMessageService.php src/Services/FacebookMessageService.php src/Services/WebhookProcessors/MessengerWebhookProcessor.php
    Esperado: Sin coincidencias (exit code 1)
    Evidencia: .sisyphus/evidence/task-9-clean.txt

  Escenario: CHANGELOG actualizado
    Herramienta: Bash
    Pasos: grep "Messenger" CHANGELOG.md | head -3
    Esperado: Entrada sobre implementación de Messenger
    Evidencia: .sisyphus/evidence/task-9-changelog.txt
  ```

  **Commit**: SÍ | Mensaje: `chore(messenger): verificación final, imports y CHANGELOG` | Archivos: [`CHANGELOG.md`, archivos con correcciones menores]

---

## 🟢 Instagram — Métodos de envío faltantes (Ola 7)

> **IMPORTANTE**: Estas tareas modifican `InstagramMessageService.php`. Son independientes de Messenger. No mezclar archivos. Solo se toca código de Instagram existente.

- [ ] 10. Agregar `sendReaction()` a InstagramMessageService

  **Qué hacer**: Agregar método `sendReaction` en `src/Services/InstagramMessageService.php`:
  ```php
  public function sendReaction(string $recipientId, string $messageId, string $reaction = '❤️'): ?array
  {
      $this->validateCredentials();
      try {
          return $this->apiClient->request(
              'POST',
              $this->instagramUserId . '/messages',
              [],
              [
                  'recipient' => ['id' => $recipientId],
                  'sender_action' => 'react',
                  'payload' => ['message_id' => $messageId],
                  'reaction' => $reaction,
              ],
              ['access_token' => $this->accessToken]
          );
      } catch (Exception $e) {
          Log::channel('instagram')->error('Error sending reaction:', ['error' => $e->getMessage()]);
          return null;
      }
  }
  ```
  Según doc oficial Meta: `POST /{IG_ID}/messages` con `sender_action: react`, `payload.message_id`, y `reaction` (emoji UTF-8).

  **Perfil de Agente**: `quick` | **Skills**: [`laravel-patterns`]
  **Paralelización**: SÍ (con 11,12,13) | Ola 7 | Bloquea: [] | Bloqueada Por: [9]
  **Commit**: SÍ | `feat(instagram): agregar sendReaction para enviar reacciones a mensajes`

- [ ] 11. Agregar `sendReply()` a InstagramMessageService

  **Qué hacer**: Agregar método para responder a un mensaje específico:
  ```php
  public function sendReply(string $recipientId, string $replyToMessageId, array $messagePayload): ?array
  {
      $this->validateCredentials();
      $payload = array_merge($messagePayload, [
          'recipient' => ['id' => $recipientId],
          'reply_to' => ['mid' => $replyToMessageId],
      ]);
      // Reutiliza sendMessageGeneric adaptado o envía directamente
      return $this->sendMessageWithPayload($recipientId, $payload);
  }
  ```
  Según doc oficial Meta: agregar `reply_to: {mid: "MESSAGE_ID"}` al payload. Funciona con cualquier tipo de mensaje (texto, media, template).

  **Perfil de Agente**: `quick` | **Skills**: [`laravel-patterns`]
  **Paralelización**: SÍ (con 10,12,13) | Ola 7 | Bloquea: [] | Bloqueada Por: [9]
  **Commit**: SÍ | `feat(instagram): agregar sendReply para responder a mensajes específicos`

- [ ] 12. Agregar `sendMultipleImages()` a InstagramMessageService

  **Qué hacer**: Agregar método para enviar hasta 10 imágenes en un solo mensaje:
  ```php
  public function sendMultipleImages(string $recipientId, array $imageUrls, ?string $conversationId = null): ?array
  {
      $this->validateCredentials();
      $attachments = array_map(fn($url) => [
          'type' => 'image',
          'payload' => ['url' => $url]
      ], $imageUrls);
      
      $payload = [
          'recipient' => ['id' => $recipientId],
          'message' => ['attachments' => $attachments],
      ];
      
      return $this->sendMessageGeneric($recipientId, $payload, 'image', $conversationId);
  }
  ```
  Según doc oficial Meta: Instagram soporta hasta 10 imágenes, Messenger hasta 30. Usar array `attachments` (no `attachment` singular).

  **Perfil de Agente**: `quick` | **Skills**: [`laravel-patterns`]
  **Paralelización**: SÍ (con 10,11,13) | Ola 7 | Bloquea: [] | Bloqueada Por: [9]
  **Commit**: SÍ | `feat(instagram): agregar sendMultipleImages para enviar hasta 10 imágenes`

- [ ] 13. Agregar `uploadAttachment()` a InstagramMessageService

  **Qué hacer**: Agregar método para subir media reusable a los servidores de Meta:
  ```php
  public function uploadAttachment(string $url, string $type = 'image'): ?string
  {
      $this->validateCredentials();
      try {
          $response = $this->apiClient->request(
              'POST',
              $this->instagramUserId . '/message_attachments',
              [],
              [
                  'message' => json_encode([
                      'attachment' => [
                          'type' => $type,
                          'payload' => [
                              'url' => $url,
                              'is_reusable' => true,
                          ],
                      ],
                  ])
              ],
              ['access_token' => $this->accessToken]
          );
          return $response['attachment_id'] ?? null;
      } catch (Exception $e) {
          Log::channel('instagram')->error('Error uploading attachment:', ['error' => $e->getMessage()]);
          return null;
      }
  }
  ```
  Según doc oficial Meta: `POST /{IG_ID}/message_attachments` con `is_reusable: true`. Retorna `attachment_id` que se puede usar en `payload.attachment_id` en vez de `payload.url` para envíos múltiples sin re-subir.

  **Perfil de Agente**: `quick` | **Skills**: [`laravel-patterns`]
  **Paralelización**: SÍ (con 10,11,12) | Ola 7 | Bloquea: [] | Bloqueada Por: [9]
  **Commit**: SÍ | `feat(instagram): agregar uploadAttachment para subir media reusable`

  **Criterios de Aceptación (tareas 10-13)**:
  - [ ] `sendReaction` existe en `InstagramMessageService`
  - [ ] `sendReply` existe con soporte `reply_to.mid`
  - [ ] `sendMultipleImages` acepta array de URLs y construye `attachments` array
  - [ ] `uploadAttachment` usa endpoint `message_attachments` con `is_reusable: true`
  - [ ] Ningún método rompe los existentes — solo se agregan, no se modifican

  **QA Scenarios**:
  ```
  Escenario: 4 nuevos métodos existen
    Herramienta: Bash
    Pasos: grep -c "function sendReaction\|function sendReply\|function sendMultipleImages\|function uploadAttachment" src/Services/InstagramMessageService.php
    Esperado: 4 coincidencias
    Evidencia: .sisyphus/evidence/task-10-13-instagram.txt
  ```

## Ola de Verificación Final (OBLIGATORIA — después de TODAS las tareas de implementación)
- [ ] F1. Auditoría de Cumplimiento del Plan — oracle
- [ ] F2. Revisión de Calidad de Código — unspecified-high
- [ ] F3. QA Manual Real — unspecified-high
- [ ] F4. Verificación de Fidelidad de Alcance — deep

## Estrategia de Commits
Cada tarea genera UN commit atómico. Secuencia:
0. `feat(messenger): agregar campos faltantes a tablas Messenger + configuración de archivos multimedia`
1. `feat(messenger): agregar ruta /facebook/connect y método connect()`
2. `feat(messenger): crear MessengerMessageService para procesamiento inbound`
3. `feat(messenger): reconstruir FacebookMessageService con soporte completo de envío`
4. `feat(messenger): crear MessengerWebhookProcessor dedicado`
5. `feat(messenger): crear 8 eventos broadcast para Messenger`
6. `feat(messenger): registrar bindings, rutas y eventos de Messenger en ServiceProvider`
7. `feat(messenger): crear MessengerWebhookController`
8. `docs(messenger): crear documentación completa de Messenger en español`
9. `chore(messenger): verificación final, imports y CHANGELOG`
10. `feat(instagram): agregar sendReaction para enviar reacciones a mensajes`
11. `feat(instagram): agregar sendReply para responder a mensajes específicos`
12. `feat(instagram): agregar sendMultipleImages para enviar hasta 10 imágenes`
13. `feat(instagram): agregar uploadAttachment para subir media reusable`

## Criterios de Éxito
- Webhook de Messenger (`/facebook-webhook`) responde a verificación GET con `hub.challenge`
- Webhook de Messenger procesa mensajes entrantes y los persiste en `messenger_messages`
- `FacebookMessageService` envía todos los tipos de mensaje: texto, imagen, audio, video, archivo, sticker, quick_replies, templates, reaction, reply, multiple images, upload attachment
- `InstagramMessageService` ahora también soporta: reaction, reply, multiple images, upload attachment
- `messaging_type` se incluye en todos los envíos a Messenger
- 8 eventos broadcast disponibles en canal `facebook-messages`
- Archivos multimedia se guardan en rutas separadas y configurables (Instagram vs Facebook)
- Documentación completa en español con 10 secciones y ejemplos funcionales
- Sin errores de linter en archivos nuevos





