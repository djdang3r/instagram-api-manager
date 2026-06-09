[◄◄ Perfil](05-perfil.md)
[▲ Tabla de contenido](00-tabla-de-contenido.md)
[Insights ►►](07-insights.md)

# 🔔 Notificaciones One-Time — Messenger

Enviá un mensaje fuera de la ventana de 24 horas solicitando permiso al usuario. Ideal para recordatorios, alertas de stock y seguimiento post-venta.

## 📋 Requisitos

- **Permiso**: `pages_messaging`
- El usuario debe haber interactuado previamente con la página
- El usuario debe aceptar explícitamente recibir la notificación

## 🚀 Flujo Completo

### Paso 1: Solicitar permiso

```php
use ScriptDevelop\InstagramApiManager\Facades\Facebook;

$token = Facebook::message()
    ->withPageAccessToken('EAAxxx...')
    ->withPageId('PAGE_ID')
    ->requestOneTimeNotification(
        recipientId: 'PSID',
        title: '¿Te avisamos cuando vuelva el stock?',
        payload: 'BACK_IN_STOCK_123'
    );

if ($token) {
    // Guardar el token para usarlo después
    // $token = "NOTIF_TOKEN_ABC123"
}
```

> 💡 El usuario ve un mensaje en Messenger preguntando si quiere recibir la notificación. Si acepta, Meta devuelve el token.

### Paso 2: Enviar la notificación (cuando corresponda)

```php
Facebook::message()
    ->withPageAccessToken('EAAxxx...')
    ->withPageId('PAGE_ID')
    ->sendOneTimeNotification(
        recipientId: 'PSID',
        token: 'NOTIF_TOKEN_ABC123',
        message: '¡Buenas noticias! El producto #123 ya está disponible 🎉'
    );
```

> ⚠️ Solo podés enviar **1 mensaje** por token. Después de usado, el token expira.

## 📊 Tabla de Métodos

| Método | Descripción | Retorno |
|--------|-------------|---------|
| `requestOneTimeNotification(string, string, string)` | Solicitar permiso al usuario | `?string` (token) |
| `sendOneTimeNotification(string, string, string)` | Enviar notificación con token | `?array` |

## ❌ Errores Comunes

| Error | Causa | Solución |
|-------|-------|----------|
| Token inválido | Token ya usado o expirado | Solicitar nuevo permiso |
| Usuario no aceptó | El usuario rechazó la solicitud | No se puede forzar |
| Fuera de ventana sin token | No se puede enviar sin one-time token | Usar `requestOneTimeNotification` primero |

## 📋 Configuration Reference

| Config Key | Tipo | Default | Descripción |
|------------|------|---------|-------------|
| `facebook.models.messenger_contact` | `string` (FQCN) | `MessengerContact::class` | Modelo Eloquent |
| `facebook.notifications.token_ttl` | `int` | `3600` | TTL del OTN token en segundos |
| `facebook.notifications.max_per_user_per_day` | `int` | `1` | Máximo de OTN por usuario por día |
| `facebook.notifications.allowed_tags` | `array` | `['HUMAN_AGENT']` | Tags válidos para OTN |

## ❓ FAQ

**¿OTN funciona en Instagram Messaging?**
**No.** OTN (One-Time Notification) es exclusivo de Messenger. Instagram no soporta este endpoint.

**¿El usuario puede recibir múltiples OTN?**
No, Meta impone 1 OTN por usuario cada 7 días por policy. Intentos adicionales retornan error 4.

**¿Cómo sé si el usuario ya usó el token?**
Meta retorna error específico `token already used`. El servicio automáticamente limpia el token de la BD.

**¿Puedo usar OTN con sponsored messages?**
No son compatibles. Sponsored messages tienen su propio endpoint y se pagan aparte.

## 🚀 Ejemplo: Flujo Completo de OTN

```php
use ScriptDevelop\InstagramApiManager\Facades\Facebook;
use Illuminate\Support\Facades\Log;

// 1. Bot detecta que el usuario completó una acción (ej: download)
$userActionAt = now()->subHours(2);
$lastContact = $user->messenger_contacts()->latest('messaged_at')->first();

if ($lastContact->messaged_at->diffInHours($userActionAt) > 24) {
    // 2. Solicitar OTN
    try {
        $token = Facebook::message()
            ->withPageAccessToken($page->page_access_token)
            ->requestOneTimeNotification(
                recipientId: $user->psid,
                title: 'Tu download está listo 📥',
                payload: 'download_ready_' . $user->id
            );

        if ($token) {
            // 3. Guardar token en BD
            $user->otn_tokens()->create([
                'token' => $token,
                'expires_at' => now()->addHour(),
                'payload' => 'download_ready_' . $user->id,
            ]);
        }
    } catch (\Exception $e) {
        Log::warning('OTN request failed', ['psid' => $user->psid, 'error' => $e->getMessage()]);
    }
}

// 4. Cuando el usuario hace clic en "Notificarme", Meta envía webhook + el token
// El listener puede usarlo para enviar el mensaje

class OneTimeNotificationClickedListener
{
    public function handle($event): void
    {
        $token = $event->token;
        $user = User::where('psid', $event->psid)->first();
        $savedToken = $user->otn_tokens()->where('token', $token)->first();

        if ($savedToken && !$savedToken->used_at) {
            // Enviar el mensaje prometido
            Facebook::message()
                ->withPageAccessToken($page->page_access_token)
                ->sendOneTimeNotification(
                    recipientId: $user->psid,
                    token: $token,
                    message: $this->buildDownloadMessage($user)
                );

            $savedToken->update(['used_at' => now()]);
        }
    }
}
```

## 📊 Casos de Uso Comunes

| Caso | Cuándo enviar OTN | Token TTL |
|------|------------------|-----------|
| Confirmación de pedido | 24h+ después del checkout | 1h |
| Recordatorio de cita | 1h antes de la cita | 24h |
| Update de envío | Cuando el paquete se mueve | 1h |
| Contenido nuevo | Lanzamiento de producto | 24h |
| Verificación de cuenta | Cuando se requiere 2FA | 5min |

## 🛠️ Troubleshooting

**Error 4: Application request limit reached**
Meta limita a 10K OTN requests/hora por app. Usá `Cache::remember()` para dedupe por usuario.

**Usuario no recibe el template OTN**
Verificar que el usuario:
1. Haya interactuado con la página en los últimos 7 días
2. Haya hecho click en el template OTN (no es push notification automática)
3. No haya reportado la página como spam

**OTN token expira antes de usar**
Default TTL es 1h. Si necesitás más, implementá retry con nuevo request.

---

[◄◄ Perfil](05-perfil.md) | [Insights ►►](07-insights.md)

## 🧪 Testing desde Proyecto Laravel Externo

Este paquete NO incluye tests internos (se testean desde un proyecto Laravel externo). Aquí está el patrón recomendado usando Testbench + PHPUnit.


## 📚 Payload del template OTN

```json
{
  "attachment": {
    "type": "template",
    "payload": {
      "template_type": "one_time_notif_req",
      "title": "Get notified when ...",
      "payload": "OTN_PAYLOAD_STRING"
    }
  }
}
```

El `payload` es un string arbitrario (max 1000 chars) que Meta te devuelve cuando el usuario hace clic.

## 🔒 Compliance con Meta Platform Policy

OTN está sujeto a estrictas reglas:
- ❌ NO contenido promotional
- ❌ NO marketing
- ✅ Transactional updates (orders, shipping, appointments)
- ✅ Account verification
- ✅ Service updates (outages, new features)

Violaciones = suspension de OTN privilege + potential Page ban.

## 📊 Tracking de OTN Performance

```php
use ScriptDevelop\InstagramApiManager\Models\MessengerContact;

// Tracking de conversiones OTN
$totalRequested = MessengerContact::where('otn_requested_at', '>=', now()->subDays(30))->count();
$totalClicked = MessengerContact::whereNotNull('otn_clicked_at')->count();
$totalSent = MessengerContact::whereNotNull('otn_sent_at')->count();

$conversionRate = $totalClicked / max($totalRequested, 1) * 100;
$engagementRate = $totalSent / max($totalClicked, 1) * 100;
```

