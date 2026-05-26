# Plan de Recomendaciones Pendientes

## TL;DR
> **Resumen**: 5 mejoras basadas en documentación oficial de Meta: `message_deliveries` webhook, CSRF en wizard, comandos artisan Messenger, tabla `messenger_referrals`, y soporte `instagram_manage_comments`.
> **Esfuerzo**: Medio
> **Archivos**: ~8 modificados, ~3 nuevos

## Contexto

Basado en análisis completo del paquete contra documentación oficial de Meta (Mayo 2026). Las 5 recomendaciones pendientes se implementan sin tocar lo que ya funciona.

---

## TODOs

### 🟡 Tarea 1: Webhook `message_deliveries` (solo Messenger)

**Qué**: Meta notifica cuando un mensaje enviado por la página fue entregado al dispositivo del usuario. Solo disponible en Messenger, no en Instagram.

**Payload oficial** (documentación Meta):
```json
{
  "sender": {"id": "PAGE_ID"},
  "recipient": {"id": "PSID"},
  "delivery": {
    "mids": ["mid.xxx", "mid.yyy"],
    "watermark": 1458668856253
  }
}
```
- `watermark`: timestamp. Todos los mensajes anteriores a este timestamp fueron entregados.
- `mids`: array opcional con IDs específicos de mensajes entregados.

**Implementación**:
1. `MessengerWebhookProcessor::processWebhookPayload()` — detectar `delivery` en `messaging[]`
2. `MessengerMessageService` — nuevo método `processDelivery(array $delivery): void`:
   - Si `mids`: actualizar `delivered_at` para cada `message_id` en el array
   - Si `watermark`: actualizar todos los mensajes `sent` anteriores al watermark a `delivered`
3. Nuevo evento: `MessengerMessageDelivered` (`ShouldBroadcastNow`, canal `facebook-messages`)

**Archivos**: `MessengerWebhookProcessor`, `MessengerMessageService`, `src/Events/MessengerMessageDelivered.php`

**Commit**: `feat(messenger): soporte para webhook message_deliveries`

---

### 🟡 Tarea 2: Completar CSRF exclusion en wizard

**Qué**: El wizard `instagram:install` solo excluye rutas de Instagram del CSRF. Debe también excluir `facebook-webhook/*` y `facebook/connect`.

**Archivo**: `src/Console/Commands/InstallInstagramApiManager.php`
- `addCsrfExclusion()` — agregar `'facebook-webhook/*'` y `'facebook/connect'` a la lista de rutas

**Commit**: `fix(wizard): excluir rutas de Messenger del CSRF`

---

### 🟢 Tarea 3: Comandos artisan para Messenger

**Qué**: Crear 2 comandos equivalentes a los de Instagram:

1. **`messenger:refresh-tokens`**: 
   - Buscar `FacebookPage` con token próximo a expirar
   - Usar `FacebookAccountService::refreshAndStoreLongLivedToken()`
   - Misma estructura que `instagram:refresh-tokens`

2. **`messenger:sync-conversations`**:
   - Sincronizar conversaciones de Messenger desde API
   - Similar a `instagram:sync-conversations`

**Archivos**: `src/Console/Commands/RefreshMessengerTokens.php`, `src/Console/Commands/SyncMessengerConversations.php`

**Registrar** en `InstagramServiceProvider::boot()` → `$this->commands([...])`

**Commit**: `feat(messenger): comandos artisan messenger:refresh-tokens y messenger:sync-conversations`

---

### 🟢 Tarea 4: Tabla `messenger_referrals`

**Qué**: Instagram tiene tabla `instagram_referrals` para tracking de referidos. Messenger guarda los referrals en la conversación (`last_referral`, `referral_source`). Crear tabla separada para consistencia.

**Migración**: `create_messenger_referrals_table`:
```php
Schema::create('messenger_referrals', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->ulid('conversation_id');
    $table->string('messenger_user_id');
    $table->string('page_id');
    $table->string('ref_parameter')->nullable();
    $table->string('source')->nullable();
    $table->string('type')->nullable();
    $table->timestamp('processed_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->foreign('conversation_id')->references('id')->on('messenger_conversations')->onDelete('cascade');
    $table->foreign('page_id')->references('page_id')->on('facebook_pages')->onDelete('cascade');
});
```

**Modelo**: `MessengerReferral` — mismo patrón que `InstagramReferral`

**Actualizar**: `MessengerMessageService::processReferral()` para guardar en tabla separada

**Commit**: `feat(messenger): tabla messenger_referrals para tracking de referidos`

---

### 🔵 Tarea 5: Soporte `instagram_manage_comments` (OPCIONAL — baja prioridad)

**Qué**: Instagram soporta webhooks para comentarios y menciones. El paquete no los maneja.

**Documentación oficial**:
- Webhook field: `comments`, `live_comments`, `mentions`
- Payload: `object: "instagram"`, `entry[].changes[].field: "comments"`, `value.comment_id`, `value.media.id`, `value.from`, `value.text`
- Permisos requeridos: `instagram_manage_comments`, `instagram_basic`
- Endpoints: `GET /{ig-user-id}/mentioned_comment`, `GET /{ig-user-id}/mentioned_media`, `POST /{ig-user-id}/mentions`

**Implementación** (si se decide hacer):
1. Nuevo `CommentWebhookProcessor` o extender `BaseWebhookProcessor`
2. Manejo de `entry[].changes[]` (formato distinto a `entry[].messaging[]`)
3. Nuevos modelos: `InstagramComment`, `InstagramMention`
4. Endpoint para responder comentarios

**Esfuerzo**: Alto (nueva funcionalidad completa, ~6 archivos nuevos)

---

## Verificación

```bash
# message_deliveries detectado en processor
grep -c "delivery" src/Services/WebhookProcessors/MessengerWebhookProcessor.php  # 1+

# CSRF exclusion en wizard
grep -c "facebook-webhook" src/Console/Commands/InstallInstagramApiManager.php  # 1+

# Comandos registrados
grep -c "messenger:" src/Providers/InstagramServiceProvider.php  # 2+
```

## Estrategia de Commits
5 commits atómicos, uno por tarea. Tareas 1 y 2 primero (funcionalidad + seguridad). Tareas 3 y 4 después (completitud). Tarea 5 es opcional.

## Criterios de Éxito
- `message_deliveries` actualiza `delivered_at` en `messenger_messages`
- CSRF wizard cubre todas las rutas del paquete
- `messenger:refresh-tokens` funcional
- `messenger_referrals` tabla creada con modelo y relaciones
