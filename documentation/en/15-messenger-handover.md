[◄◄ Insights](07-insights.md)
[▲ Tabla de contenido](00-tabla-de-contenido.md)

# 🔄 Handover Protocol — Messenger

Transferí el control de una conversación entre aplicaciones, bots y el inbox de Facebook. Ideal para flujos bot → humano.

## 📋 Requisitos

- **Permiso**: `pages_messaging`
- La app destino debe estar configurada como Secondary Receiver en Meta Dashboard

## 🚀 Uso

```php
use ScriptDevelop\InstagramApiManager\Facades\Facebook;

$handover = Facebook::handover()
    ->withPageAccessToken('EAAxxx...');
```

### Pasar control a otra app

```php
// Pasar conversación al inbox de Facebook (humano)
$handover->passThreadControl(
    recipientId: 'PSID',
    targetAppId: '263902037430900'  // Page Inbox
);
```

### Tomar control

```php
$handover->takeThreadControl('PSID');
```

### Ver apps disponibles

```php
$receivers = $handover->getSecondaryReceivers();
// [{id: '263902...', name: 'Page Inbox'}, ...]
```

## 📊 Tabla de Métodos

| Método | Descripción | Retorno |
|--------|-------------|---------|
| `passThreadControl` | Pasar control a otra app | `bool` |
| `takeThreadControl` | Tomar control de conversación | `bool` |
| `getSecondaryReceivers` | Listar apps disponibles | `?array` |

## 🔄 Flujo Típico

```
Usuario escribe → Bot responde → Usuario pide humano
                                        │
                          $handover->passThreadControl($psid, 'page_inbox')
                                        │
                          Humano responde desde Facebook Inbox
```

## 📋 Configuration Reference

| Config Key | Tipo | Default | Descripción |
|------------|------|---------|-------------|
| `facebook.models.messenger_conversation` | `string` (FQCN) | `MessengerConversation::class` | Modelo Eloquent |
| `facebook.handover.primary_receiver_app_id` | `string` (env) | — | App ID del receiver primario (bot) |
| `facebook.handover.secondary_receivers` | `array` | `[]` | Array de App IDs secundarios (humanos) |
| `facebook.handover.pass_on_keywords` | `array` | `['humano', 'persona real', 'agente']` | Keywords para auto-pasar a humano |
| `facebook.handover.webhook_events` | `array` | `['pass_thread_control', 'take_thread_control', 'request_thread_control']` | Eventos a escuchar |
| `facebook.handover.metadata_max_length` | `int` | `1000` | Tamaño máximo del metadata string |

## ❓ FAQ

**¿Handover Protocol funciona en Instagram Messaging?**
**No todavía.** Meta está trabajando en soportarlo para IG. Por ahora, solo Messenger.

**¿Puedo tener varios bots como primary receivers?**
No, solo 1 primary. Pero sí múltiples secondary (humanos) que pueden pasarse el control entre sí.

**¿Qué pasa si un humano no responde en X minutos?**
El control vuelve automáticamente al bot después de 24h de inactividad, o manualmente vía `takeThreadControl()`.

**¿Los webhooks de handover disparan eventos Laravel?**
Sí, hay 3 eventos broadcast: `HandoverPassed`, `HandoverTaken`, `HandoverRequested`. Listalos con `Event::listen()`.

## 🚀 Ejemplo: Auto-Handover por Keywords

```php
use ScriptDevelop\InstagramApiManager\Facades\Facebook;
use ScriptDevelop\InstagramApiManager\Events\MessengerMessageReceived;

class AutoHandoverListener
{
    public function handle(MessengerMessageReceived $event): void
    {
        $message = strtolower($event->message['text'] ?? '');
        $keywords = config('facebook.handover.pass_on_keywords', ['humano', 'persona real', 'agente']);

        if (collect($keywords)->contains(fn($kw) => str_contains($message, $kw))) {
            $handover = Facebook::handover()
                ->withPageAccessToken($event->page_access_token);

            $result = $handover->passThreadControl(
                recipientId: $event->sender['id'],
                targetAppId: config('facebook.handover.secondary_receivers')[0] ?? 'page_inbox',
                metadata: json_encode(['reason' => 'user_requested_human'])
            );

            if ($result) {
                // Notificar al usuario
                Facebook::message()
                    ->withPageAccessToken($event->page_access_token)
                    ->sendTextMessage(
                        recipientId: $event->sender['id'],
                        text: 'Te conecto con un humano. Un agente te responderá en breve. 🙏'
                    );
            }
        }
    }
}
```

## 🎯 Estrategias de Handover

| Estrategia | Cuándo aplicar | Implementación |
|------------|----------------|----------------|
| **Keyword-based** | Usuario pide explícitamente humano | Listener que detecta keywords |
| **Sentiment-based** | Bot detecta frustración (palabras negativas) | Integrar con servicio NLP |
| **Time-based** | Bot no responde en N segundos | Timeout con auto-pass |
| **Confidence-based** | Bot NLU tiene baja confianza en intent | Pre-handover warning al usuario |
| **Escalation tree** | Issue complejo que excede capacidades | Decision tree en el listener |

## 🔄 Listeners Recomendados

```php
// EventServiceProvider.php
protected $listen = [
    'ScriptDevelop\InstagramApiManager\Events\HandoverPassed' => [
        \App\Listeners\LogHandoverToAnalytics::class,
        \App\Listeners\NotifySupervisorOnEscalation::class,
    ],
    'ScriptDevelop\InstagramApiManager\Events\HandoverTaken' => [
        \App\Listeners\ResumeBotAfterHumanDone::class,
    ],
    'ScriptDevelop\InstagramApiManager\Events\HandoverRequested' => [
        \App\Listeners\AutoApproveIfPrimaryReceiver::class,
    ],
];
```

## 🛠️ Troubleshooting

**Error 10: Permission denied**
Tu app debe tener `pages_messaging` y estar aprobada para handover protocol. App Review required.

**Bot sigue respondiendo después de handover**
Verificar que el bot NO esté subscrito a webhooks como secondary receiver. Solo el PRIMARY debe recibir eventos después del handover.

**Handover falla con "target_app_id not found"**
El `target_app_id` debe ser de una app aprobada y agregada como secondary receiver en App Dashboard.

**Control no vuelve al bot después de 24h**
Meta no tiene auto-timeout. Implementar job programado que llame `takeThreadControl()` después de X horas de inactividad.

---

[◄◄ Insights](07-insights.md)

## 🧪 Testing desde Proyecto Laravel Externo

Este paquete NO incluye tests internos (se testean desde un proyecto Laravel externo). Aquí está el patrón recomendado usando Testbench + PHPUnit.


## 📚 Payload de Webhook Handover

```json
{
  "object": "page",
  "entry": [{
    "id": "PAGE_ID",
    "time": 1620000000,
    "standby": [{
      "sender": {"id": "PSID"},
      "recipient": {"id": "PAGE_ID"},
      "timestamp": 1620000000,
      "pass_thread_control": {
        "new_owner_app_id": "SECONDARY_APP_ID",
        "metadata": "{\"reason\": \"user_requested_human\"}"
      }
    }]
  }]
}
```

## 🔄 Estados de Conversación

| Estado | Quién responde | Cómo se llega |
|--------|---------------|---------------|
| `BOT_ACTIVE` | Bot (primary) | Default |
| `HUMAN_ACTIVE` | Humano (secondary) | `passThreadControl()` |
| `HUMAN_PENDING` | Bot | `requestThreadControl()` aún no aprobado |
| `HUMAN_INACTIVE` | Bot | Auto-timeout o `takeThreadControl()` |

## 🔐 Permisos Requeridos

| Scope | Primary | Secondary |
|-------|---------|-----------|
| `pages_messaging` | ✅ | ✅ |
| `pages_messaging_phone_number` | ❌ | ✅ (si usa inbox) |
| App Review | Required for production | Required for production |

