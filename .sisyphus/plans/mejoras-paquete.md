# Plan de Mejoras — Instagram API Manager

## TL;DR
> **Resumen**: 9 mejoras organizadas en 3 olas de prioridad: Seguridad (X-Hub-Signature, rate limiting), Confiabilidad (token refresh FB, deduplicación, validación 24h), y Completitud (tabla media Messenger, message_deliveries, comandos artisan).
> **Esfuerzo**: Medio
> **Paralelo**: SÍ — tareas independientes dentro de cada ola
> **Archivos**: ~12 modificados, ~3 nuevos

## Contexto

Análisis completo del paquete contra documentación oficial de Meta (Mayo 2026). Se identificaron 9 gaps. Las mejoras se organizan por prioridad sin tocar lo que ya funciona.

## Estrategia de Ejecución

### Ola 1: Seguridad 🔴 (urgente — producción)
| Tarea | Qué | Archivos |
|-------|-----|----------|
| 1 | Validar X-Hub-Signature-256 en webhooks | `BaseWebhookProcessor`, `MessengerWebhookProcessor` |
| 2 | Rate limiting en endpoints webhook | `InstagramWebhookController`, `MessengerWebhookController` + `config/instagram.php`, `config/facebook.php` |

### Ola 2: Confiabilidad 🟡 (alta)
| 3 | Token refresh para Facebook | `FacebookAccountService` |
| 4 | Deduplicación de webhooks | `BaseWebhookProcessor`, `MessengerWebhookProcessor`, `InstagramMessageService`, `MessengerMessageService` |
| 5 | Validación de ventana 24h | `InstagramMessageService`, `FacebookMessageService` |

### Ola 3: Completitud 🟢 (media-baja)
| 6 | Tabla `messenger_media_messages` + modelo | Migración + modelo + relación en `MessengerMessage` |
| 7 | Soporte para `message_deliveries` webhook | `MessengerWebhookProcessor`, `MessengerMessageService`, nuevo evento |
| 8 | Comandos artisan para Messenger | `messenger:refresh-tokens`, `messenger:sync-conversations` |
| 9 | Actualizar documentación y CHANGELOG | `documentation/es/messenger/`, `CHANGELOG.md` |

## TODOs

### 🔴 Ola 1: Seguridad

- [ ] 1. Validar X-Hub-Signature-256 en webhooks

  **Qué hacer**: Meta firma todos los payloads con SHA256 usando el App Secret. El header `X-Hub-Signature-256: sha256={firma}` llega en cada POST. Hay que validarlo en ambos procesadores de webhook.

  1. Crear trait `src/Traits/ValidatesHubSignature.php`:
     ```php
     trait ValidatesHubSignature
     {
         protected function validateHubSignature(Request $request, string $appSecret): bool
         {
             $signature = $request->header('X-Hub-Signature-256');
             if (!$signature) return false;
             
             $payload = $request->getContent();
             $expected = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);
             
             return hash_equals($expected, $signature);
         }
         
         protected function getAppSecret(string $platform = 'instagram'): string
         {
             return config("{$platform}.meta_auth.client_secret");
         }
     }
     ```

  2. Usar el trait en `BaseWebhookProcessor::processWebhookPayload()` — validar ANTES de procesar, retornar 403 si falla.
  3. Usar el trait en `MessengerWebhookProcessor::processWebhookPayload()` — ídem con `facebook.meta_auth.client_secret`.
  4. La verificación GET (hub.verify_token) no necesita validación de firma (Meta no la envía en GET).

  **NO debe hacer**:
  - NO romper el flujo de verificación GET — solo validar en POST
  - NO hardcodear el app secret — usar `config()`

  **Commit**: `feat(security): validar X-Hub-Signature-256 en webhooks`

- [ ] 2. Rate limiting en webhooks

  **Qué hacer**: Meta tiene límites de rate (error 613). Para prevenir abusos y cumplir buenas prácticas:

  1. Agregar middleware de rate limiting a las rutas de webhook en `InstagramServiceProvider::boot()`:
     ```php
     Route::prefix('instagram-webhook')->middleware('throttle:60,1')->group(...);
     Route::prefix('facebook-webhook')->middleware('throttle:60,1')->group(...);
     ```
  2. Agregar configuración en `config/instagram.php` y `config/facebook.php`:
     ```php
     'rate_limit' => [
         'max_attempts' => env('INSTAGRAM_RATE_LIMIT', 60),
         'decay_minutes' => env('INSTAGRAM_RATE_LIMIT_DECAY', 1),
     ],
     ```

  **NO debe hacer**:
  - NO aplicar rate limiting a rutas de callback OAuth (connect/callback)
  - NO usar valores fijos — usar configuración

  **Commit**: `feat(security): rate limiting en webhooks con configuración por plataforma`

### 🟡 Ola 2: Confiabilidad

- [ ] 3. Token refresh para Facebook

  **Qué hacer**: `FacebookAccountService` no tiene `refreshLongLivedToken()` ni `refreshAndStoreLongLivedToken()`. Instagram sí. Replicar:

  1. Agregar `refreshLongLivedToken(Model $page): ?array` a `FacebookAccountService`:
     - Usar endpoint `GET /oauth/access_token?grant_type=fb_exchange_token&fb_exchange_token={PAGE_TOKEN}&client_id=...&client_secret=...`
     - O usar `POST /{PAGE_ID}/subscribed_apps` si aplica
  2. Agregar `refreshAndStoreLongLivedToken(Model $page): bool`
  3. Agregar comando `messenger:refresh-tokens` (task 8)

  **Referencia**: `InstagramAccountService::refreshLongLivedToken()` líneas 387-439 y `refreshAndStoreLongLivedToken()` líneas 447-458.

  **Commit**: `feat(messenger): token refresh para páginas de Facebook`

- [ ] 4. Deduplicación de webhooks

  **Qué hacer**: Meta reenvía webhooks si no recibe 200 OK en 5 segundos. Ya existe verificación por `message_id` en `processIncomingMessage()` (línea 388 en Instagram, línea similar en Messenger), pero falta para otros eventos.

  1. En `BaseWebhookProcessor::processWebhookPayload()` y `MessengerWebhookProcessor`:
     - Extraer `mid` o `message_id` del payload ANTES de procesar
     - Verificar si ya existe en BD
     - Si existe → log y saltar (retornar 200 OK para que Meta no reenvíe)
  2. En los métodos `processPostback()`, `processReaction()`, `processRead()` de ambos servicios:
     - Verificar duplicados por `message_id` o `mid`

  **Commit**: `fix: deduplicación de webhooks para prevenir procesamiento duplicado`

- [ ] 5. Validación de ventana de 24h

  **Qué hacer**: Meta solo permite envío de mensajes dentro de 24h desde el último mensaje del usuario (error 1545041 si se excede).

  1. En `InstagramMessageService::sendMessageGeneric()` y `FacebookMessageService::sendMessageGeneric()`:
     - Verificar `last_message_at` de la conversación
     - Si pasaron más de 24h y no es `MESSAGE_TAG` → lanzar excepción o log warning
     - Si es `MESSAGE_TAG`, permitir
  2. Agregar método helper `isWithin24hWindow(Model $conversation): bool`

  **NO debe hacer**:
  - NO bloquear envíos con `MESSAGE_TAG` — esos son legítimos fuera de 24h
  - NO modificar el parámetro `messaging_type` automáticamente

  **Commit**: `feat: validación de ventana de 24h antes de enviar mensajes`

### 🟢 Ola 3: Completitud

- [ ] 6. Crear tabla `messenger_media_messages` + modelo

  **Qué hacer**: Instagram tiene `instagram_media_messages` para separar attachments de mensajes. Messenger debe tener lo mismo.

  1. **Migración**: `create_messenger_media_messages_table`:
     ```php
     Schema::create('messenger_media_messages', function (Blueprint $table) {
         $table->ulid('media_id')->primary();
         $table->string('message_id');
         $table->string('media_type');  // image, video, audio, file
         $table->string('media_url');   // URL pública o ruta local
         $table->string('local_path')->nullable(); // ruta en storage
         $table->json('json')->nullable();
         $table->timestamps();
         $table->softDeletes();
         $table->foreign('message_id')->references('message_id')->on('messenger_messages')->onDelete('cascade');
         $table->index('message_id');
     });
     ```

  2. **Modelo**: `src/Models/MessengerMediaMessage.php`:
     ```php
     class MessengerMediaMessage extends Model
     {
         use SoftDeletes, GeneratesUlid;
         protected $table = 'messenger_media_messages';
         protected $primaryKey = 'media_id';
         protected $keyType = 'string';
         public $incrementing = false;
         protected $fillable = ['media_id', 'message_id', 'media_type', 'media_url', 'local_path', 'json'];
         protected $casts = ['json' => 'array'];
         
         public function message(): BelongsTo {
             return $this->belongsTo(config('instagram.models.messenger_message'), 'message_id', 'message_id');
         }
     }
     ```

  3. **Relación en MessengerMessage**: agregar `media(): HasMany` como en `InstagramMessage`
  4. **Actualizar MessengerMessageService**: `processAttachments()` debe guardar en `messenger_media_messages` en vez de solo en `attachments` JSON
  5. **Registrar en config/facebook.php** sección models

  **Patrón de referencia**: `src/Models/InstagramMediaMessage.php` y su migración `2026_05_11_000001_create_instagram_media_messages_table.php`

  **Commit**: `feat(messenger): tabla messenger_media_messages para separar archivos multimedia`

- [ ] 7. Soporte para webhook `message_deliveries`

  **Qué hacer**: Messenger tiene el campo `message_deliveries` (no disponible en Instagram) que notifica cuando un mensaje fue entregado al dispositivo del usuario.

  1. En `MessengerWebhookProcessor::processWebhookPayload()`:
     - Agregar manejo de `entry[].messaging[]` con `delivery` field
  2. En `MessengerMessageService`:
     - Agregar `processDelivery(array $delivery, string $pageId): void`
     - Buscar mensaje por `message_id` y actualizar `delivered_at`
  3. Nuevo evento: `MessengerMessageDelivered` (broadcast en `facebook-messages`)

  **Estructura del payload**:
  ```json
  {
    "sender": {"id": "PAGE_ID"},
    "recipient": {"id": "PSID"},
    "delivery": {
      "mids": ["mid.xxx", "mid.yyy"],
      "watermark": 1458692752478
    }
  }
  ```

  **Commit**: `feat(messenger): soporte para webhook message_deliveries`

- [ ] 8. Comandos artisan para Messenger

  **Qué hacer**: Crear comandos equivalentes a los de Instagram:

  1. `messenger:refresh-tokens` — refrescar tokens de páginas FB que estén por expirar
  2. `messenger:sync-conversations` — sincronizar conversaciones de Messenger desde la API

  **Commit**: `feat(messenger): comandos artisan messenger:refresh-tokens y messenger:sync-conversations`

- [ ] 9. Actualizar documentación y CHANGELOG

  **Qué hacer**:
  1. `documentation/es/messenger/` — agregar secciones sobre:
     - Seguridad (X-Hub-Signature en `03-webhooks.md`)
     - Rate limiting (en `02-configuracion.md`)
     - Tabla media (en `02-mensajes.md`)
     - message_deliveries (en `03-webhooks.md`)
  2. `CHANGELOG.md` — todas las mejoras en `[Unreleased]`

  **Commit**: `docs: documentar mejoras de seguridad, confiabilidad y media`

## Verificación

```bash
# Sin errores de linter en archivos modificados
# X-Hub-Signature validado en ambos webhooks
grep -c "X-Hub-Signature-256" src/Services/WebhookProcessors/*.php  # 2 coincidencias
# Rate limiting configurado
grep -c "throttle" src/Providers/InstagramServiceProvider.php  # 2 coincidencias
# Tabla media creada
ls database/migrations/*messenger_media*  # 1 archivo
```

## Estrategia de Commits
9 commits atómicos, uno por tarea. Commits dentro de cada ola pueden ir en cualquier orden. Commits de ola 1 (seguridad) deben ir primero.

## Criterios de Éxito
- X-Hub-Signature validado en webhooks de Instagram y Messenger (403 si firma inválida)
- Rate limiting activo en endpoints webhook (configurable)
- FacebookAccountService puede refrescar tokens de página
- Webhooks duplicados no procesan mensajes repetidos
- Envíos fuera de ventana 24h generan advertencia
- Tabla messenger_media_messages existe con modelo y relaciones
- message_deliveries procesa y persiste delivered_at
- Comandos artisan registrados y funcionales
