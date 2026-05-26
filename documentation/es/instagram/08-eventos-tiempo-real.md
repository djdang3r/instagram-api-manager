[◄◄ Webhooks y Eventos](07-webhooks.md)
[▲ Tabla de contenido](00-tabla-de-contenido.md)

# 📡 Eventos en Tiempo Real (Laravel Reverb)

A partir de la versión `1.0.82`, el paquete incluye soporte nativo para **Laravel Reverb** y cualquier driver de broadcasting compatible con Laravel (Pusher, Ably, Soketi, etc.). Esto te permite recibir notificaciones en tiempo real cada vez que ocurre un evento en tus webhooks de Instagram, sin necesidad de hacer polling.

---

## 🏗️ Arquitectura del Sistema de Eventos

```
┌─────────────────┐     ┌──────────────────────┐     ┌───────────────────┐
│  Instagram/Meta │ ──▶ │  InstagramWebhook    │ ──▶ │  WebhookProcessor │
│  Webhook POST   │     │  Controller          │     │  (Interface)      │
└─────────────────┘     └──────────────────────┘     └────────┬──────────┘
                                                               │
                                              ┌────────────────┼────────────────┐
                                              ▼                ▼                ▼
                                     ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
                                     │ MessageService│ │ Event Dispatch│ │  Tu Procesador│
                                     │ (procesa en BD)│ │ (broadcast)  │ │  Personalizado│
                                     └──────────────┘ └──────┬───────┘ └──────────────┘
                                                              │
                                                    ┌─────────▼─────────┐
                                                    │  Laravel Reverb   │
                                                    │  (WebSocket)      │
                                                    └─────────┬─────────┘
                                                              │
                                                    ┌─────────▼─────────┐
                                                    │  Laravel Echo     │
                                                    │  (Frontend JS)    │
                                                    └───────────────────┘
```

### Flujo de Datos

1. **Meta envía** un webhook `POST` a tu endpoint `/instagram-webhook`
2. **`InstagramWebhookController`** recibe la petición y la delega al procesador configurado
3. **`BaseWebhookProcessor`** (o tu procesador personalizado):
   - Itera las entradas y mensajes del webhook
   - Llama a `InstagramMessageService::processWebhookMessage()` para almacenar en BD
   - Determina el tipo de evento (mensaje, reacción, postback, etc.)
   - **Dispara el evento broadcast** correspondiente
4. **Laravel Reverb** transmite el evento vía WebSocket a todos los clientes suscritos
5. **Laravel Echo** (en tu frontend) recibe el evento y ejecuta tu lógica

---

## ⚙️ Configuración

### Variables de Entorno

Añade estas variables a tu archivo `.env`:

```env
# Tipo de canal de broadcast: 'public' o 'private'
INSTAGRAM_BROADCAST_CHANNEL_TYPE=public

# Si es 'true', el paquete NO carga sus rutas de canal — tú defines las tuyas en routes/channels.php
INSTAGRAM_CUSTOM_CHANNELS=false

# Procesador de webhook personalizado (opcional — por defecto usa BaseWebhookProcessor)
INSTAGRAM_WEBHOOK_PROCESSOR=\ScriptDevelop\InstagramApiManager\Services\WebhookProcessors\BaseWebhookProcessor

# Driver de broadcasting (debe estar configurado en tu proyecto Laravel)
BROADCAST_CONNECTION=reverb
```

### Configuración en `config/instagram.php`

Si publicaste la configuración del paquete, encontrarás estas nuevas secciones:

```php
'broadcast' => [
    'channel_type'    => env('INSTAGRAM_BROADCAST_CHANNEL_TYPE', 'public'),
    'custom_channels' => env('INSTAGRAM_CUSTOM_CHANNELS', false),
],

'events' => [
    'message'      => \ScriptDevelop\InstagramApiManager\Events\InstagramMessageReceived::class,
    'postback'     => \ScriptDevelop\InstagramApiManager\Events\InstagramPostbackReceived::class,
    'reaction'     => \ScriptDevelop\InstagramApiManager\Events\InstagramReactionReceived::class,
    'optin'        => \ScriptDevelop\InstagramApiManager\Events\InstagramOptinReceived::class,
    'referral'     => \ScriptDevelop\InstagramApiManager\Events\InstagramReferralReceived::class,
    'read'         => \ScriptDevelop\InstagramApiManager\Events\InstagramReadReceived::class,
    'message_edit' => \ScriptDevelop\InstagramApiManager\Events\InstagramMessageEdited::class,
],

'webhook' => [
    'verify_token' => env('INSTAGRAM_WEBHOOK_VERIFY_TOKEN', 'default_token'),
    'processor'    => env('INSTAGRAM_WEBHOOK_PROCESSOR', \ScriptDevelop\InstagramApiManager\Services\WebhookProcessors\BaseWebhookProcessor::class),
],
```

### Requisitos Previos

El paquete **no instala Laravel Reverb automáticamente** — eso es responsabilidad tuya como parte de la configuración de tu proyecto Laravel. Asegúrate de tener:

1. **Laravel Reverb instalado y configurado** en tu proyecto:
```bash
php artisan reverb:install
```

2. **Broadcasting habilitado** en `config/app.php` (viene por defecto en Laravel 11+).

3. **Laravel Echo instalado** en tu frontend:
```bash
npm install laravel-echo pusher-js
```

---

## 📡 Canales de Broadcast

El paquete define un **único canal principal** para todos los eventos de mensajería:

| Canal | Tipo | Propósito |
|---|---|---|
| `instagram-messages` | `public` o `private` | Todos los eventos de mensajería entrante |

### Canal Público (`INSTAGRAM_BROADCAST_CHANNEL_TYPE=public`)

Cualquier cliente WebSocket puede suscribirse sin autenticación. Ideal para dashboards públicos o monitoreo.

```js
// En tu frontend JavaScript
Echo.channel('instagram-messages')
    .listen('InstagramMessageReceived', (e) => {
        console.log('Nuevo mensaje:', e.data);
    });
```

### Canal Privado (`INSTAGRAM_BROADCAST_CHANNEL_TYPE=private`)

Solo usuarios autenticados pueden suscribirse. El paquete define la autorización en `src/routes/channels.php`:

```php
Broadcast::channel('instagram-messages', function ($user) {
    return $user !== null; // Personaliza esta lógica según tu app
});
```

Para usar canales privados, necesitas publicar las rutas de canal y personalizarlas:

```bash
php artisan vendor:publish --tag=instagram-channels
```

Esto copia `src/routes/channels.php` a `routes/channels.php` de tu proyecto. Si activas `INSTAGRAM_CUSTOM_CHANNELS=true`, el paquete **no cargará** sus propias rutas — usarás exclusivamente las tuyas.

---

## 🎯 Tipos de Eventos

El paquete dispara **7 tipos de eventos**, uno por cada tipo de mensaje de Instagram:

### 1. `InstagramMessageReceived`

Se dispara cuando el webhook recibe un **mensaje entrante** (texto, imagen, video, audio, quick reply, archivo adjunto).

**Payload:**
```json
{
    "sender": "17841405822304228",
    "recipient": "17841405822304214",
    "timestamp": 1746493200000,
    "data": {
        "mid": "aWdfZAG1h...",
        "text": "Hola, quiero información",
        "quick_reply": null,
        "attachments": null
    }
}
```

### 2. `InstagramPostbackReceived`

Se dispara cuando un usuario hace clic en un **botón de acción** (CTA) en un mensaje.

**Payload:**
```json
{
    "sender": "17841405822304228",
    "recipient": "17841405822304214",
    "timestamp": 1746493200000,
    "data": {
        "title": "Comprar ahora",
        "payload": "BUY_NOW_PAYLOAD",
        "mid": "aWdfZAG1h..."
    }
}
```

### 3. `InstagramReactionReceived`

Se dispara cuando un usuario **reacciona** a un mensaje (like, heart, etc.).

**Payload:**
```json
{
    "sender": "17841405822304228",
    "recipient": "17841405822304214",
    "timestamp": 1746493200000,
    "data": {
        "mid": "aWdfZAG1h...",
        "reaction": "like",
        "emoji": "❤️",
        "action": "react"
    }
}
```

### 4. `InstagramOptinReceived`

Se dispara cuando un usuario acepta recibir mensajes (opt-in) a través de un plugin de WhatsApp, anuncio, etc.

**Payload:**
```json
{
    "sender": "17841405822304228",
    "recipient": "17841405822304214",
    "timestamp": 1746493200000,
    "data": {
        "ref": "optin_ref_123",
        "user_ref": "user_ref_456"
    }
}
```

### 5. `InstagramReferralReceived`

Se dispara cuando un usuario llega a través de un **enlace de referencia** (ig.me, anuncio, shortlink, etc.).

**Payload:**
```json
{
    "sender": "17841405822304228",
    "recipient": "17841405822304214",
    "timestamp": 1746493200000,
    "data": {
        "ref": "campana_verano_2025",
        "source": "SHORTLINKS",
        "type": "open_thread"
    }
}
```

### 6. `InstagramReadReceived`

Se dispara cuando un mensaje que **tú enviaste** es marcado como leído por el usuario.

**Payload:**
```json
{
    "sender": "17841405822304228",
    "recipient": "17841405822304214",
    "timestamp": 1746493200000,
    "data": {
        "watermark": 1746493200000,
        "mid": "aWdfZAG1h..."
    }
}
```

### 7. `InstagramMessageEdited`

Se dispara cuando un usuario **edita** un mensaje previamente enviado.

**Payload:**
```json
{
    "sender": "17841405822304228",
    "recipient": "17841405822304214",
    "timestamp": 1746493200000,
    "data": {
        "mid": "aWdfZAG1h..."
    }
}
```

---

## 💻 Escuchando Eventos desde el Frontend

### Con Laravel Echo (JavaScript)

```js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

// Escuchar todos los mensajes entrantes
Echo.channel('instagram-messages')
    .listen('InstagramMessageReceived', (event) => {
        console.log('📨 Nuevo mensaje de Instagram:', event.data);

        // Actualizar tu UI
        addMessageToChat(event.data);
    })
    .listen('InstagramReactionReceived', (event) => {
        console.log('❤️ Reacción recibida:', event.data.emoji);

        // Actualizar reacciones en UI
        updateReactions(event.data);
    })
    .listen('InstagramPostbackReceived', (event) => {
        console.log('🔘 Postback:', event.data.payload);

        // Redirigir o ejecutar acción
        handlePostback(event.data.payload);
    })
    .listen('InstagramReadReceived', (event) => {
        console.log('✅ Mensaje leído por el usuario');

        // Marcar como leído en UI
        markAsRead(event.data);
    });
```

### Con Alpine.js

```html
<div x-data="instagramEvents()" x-init="init()">
    <template x-for="msg in messages" :key="msg.id">
        <div class="message" x-text="msg.data.text || msg.data.title"></div>
    </template>
</div>

<script>
function instagramEvents() {
    return {
        messages: [],

        init() {
            Echo.channel('instagram-messages')
                .listen('InstagramMessageReceived', (e) => {
                    this.messages.push({ id: Date.now(), ...e });
                })
                .listen('InstagramReactionReceived', (e) => {
                    this.messages.push({ id: Date.now(), ...e });
                });
        }
    }
}
</script>
```

### Con Livewire

```php
// En tu componente Livewire
use Livewire\Component;

class InstagramInbox extends Component
{
    public $messages = [];

    public function getListeners()
    {
        return [
            'echo:instagram-messages,InstagramMessageReceived' => 'onMessageReceived',
            'echo:instagram-messages,InstagramReactionReceived' => 'onReactionReceived',
            'echo:instagram-messages,InstagramReadReceived' => 'onReadReceived',
        ];
    }

    public function onMessageReceived($event)
    {
        $this->messages[] = $event['data'];
    }

    public function onReactionReceived($event)
    {
        // Actualizar reacciones
    }

    public function onReadReceived($event)
    {
        // Marcar como leído
    }
}
```

---

## 🎧 Escuchando Eventos desde el Backend (Listeners de Laravel)

También puedes escuchar los eventos en el backend sin necesidad de WebSockets — son eventos de Laravel estándar que puedes capturar con listeners:

### Registrar un Listener

```php
// En App\Providers\EventServiceProvider.php

use ScriptDevelop\InstagramApiManager\Events\InstagramMessageReceived;
use App\Listeners\AutoReplyToInstagramMessage;

protected $listen = [
    InstagramMessageReceived::class => [
        AutoReplyToInstagramMessage::class,
    ],
];
```

### Crear el Listener

```bash
php artisan make:listener AutoReplyToInstagramMessage
```

```php
namespace App\Listeners;

use ScriptDevelop\InstagramApiManager\Events\InstagramMessageReceived;
use ScriptDevelop\InstagramApiManager\Facades\Instagram;

class AutoReplyToInstagramMessage
{
    public function handle(InstagramMessageReceived $event): void
    {
        $data = $event->data;

        // Solo responder mensajes de texto
        if (isset($data['data']['text'])) {
            $senderId = $data['sender'];
            $messageText = $data['data']['text'];

            // Enviar respuesta automática
            Instagram::message()
                ->withAccessToken(config('instagram.meta_auth.client_secret'))
                ->withInstagramUserId($data['recipient'])
                ->sendTextMessage($senderId, "¡Gracias por tu mensaje! Te responderemos pronto.");
        }
    }
}
```

---

## 🔧 Procesador de Webhook Personalizable

Una de las características más potentes del sistema es que **puedes reemplazar completamente** la lógica de procesamiento de webhooks sin tocar el código del paquete.

### ¿Por qué personalizar el procesador?

- Necesitas una lógica de negocio específica antes/después de procesar cada mensaje
- Quieres filtrar o enriquecer los datos antes de guardarlos en BD
- Necesitas integrar con sistemas externos (CRM, analytics, notificaciones push)
- Quieres modificar el comportamiento de los eventos broadcast

### Crear tu Propio Procesador

```php
// App\Processors\MiProcesadorInstagram.php

namespace App\Processors;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use ScriptDevelop\InstagramApiManager\Contracts\WebhookProcessorInterface;
use ScriptDevelop\InstagramApiManager\Services\InstagramMessageService;

class MiProcesadorInstagram implements WebhookProcessorInterface
{
    protected InstagramMessageService $messageService;

    public function __construct(InstagramMessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    public function handle(Request $request): Response|JsonResponse
    {
        // Tu lógica de enrutamiento GET/POST aquí
        if ($request->isMethod('get')) {
            return $this->verifyWebhook($request);
        }

        return $this->processWebhookPayload($request);
    }

    public function verifyWebhook(Request $request): Response
    {
        // Tu lógica de verificación personalizada
        $challenge = $request->get('hub_challenge');
        $token = $request->get('hub_verify_token');

        if ($token === 'mi_token_secreto' && $challenge) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    public function processWebhookPayload(Request $request): JsonResponse
    {
        $data = $request->all();

        // Ejemplo: filtrar solo mensajes de ciertos usuarios VIP
        foreach ($data['entry'] ?? [] as $entry) {
            foreach ($entry['messaging'] ?? [] as $messaging) {
                $senderId = $messaging['sender']['id'] ?? null;

                // Solo procesar mensajes de usuarios VIP
                if ($this->esUsuarioVip($senderId)) {
                    // Procesar con el servicio estándar
                    $this->messageService->processWebhookMessage($messaging);

                    // Tu lógica adicional: notificar a Slack, actualizar CRM, etc.
                    $this->notificarSlack($messaging);
                }
            }
        }

        return response()->json(['success' => true]);
    }

    private function esUsuarioVip(string $senderId): bool
    {
        return cache()->has("vip_user:{$senderId}");
    }

    private function notificarSlack(array $messaging): void
    {
        // Integración con Slack, CRM, etc.
    }
}
```

### Registrar tu Procesador

En tu `.env`:

```env
INSTAGRAM_WEBHOOK_PROCESSOR=\App\Processors\MiProcesadorInstagram
```

O en `config/instagram.php`:

```php
'webhook' => [
    'processor' => \App\Processors\MiProcesadorInstagram::class,
],
```

El paquete automáticamente usará tu clase en lugar de `BaseWebhookProcessor`.

---

## 🎨 Personalización de Clases de Eventos

Puedes **reemplazar las clases de eventos** por las tuyas propias para modificar el canal, el nombre del evento, o los datos transmitidos:

```php
// En config/instagram.php
'events' => [
    'message'  => \App\Events\MiEventoMensajeInstagram::class,     // Tu propia clase
    'postback' => \App\Events\MiEventoPostbackInstagram::class,    // Tu propia clase
    // ... etc
],
```

Tu clase de evento personalizada debe implementar `ShouldBroadcast`:

```php
namespace App\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MiEventoMensajeInstagram implements ShouldBroadcast
{
    use \Illuminate\Foundation\Events\Dispatchable;
    use \Illuminate\Broadcasting\InteractsWithSockets;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function broadcastOn()
    {
        return new \Illuminate\Broadcasting\Channel('mi-canal-personalizado');
    }

    public function broadcastAs()
    {
        return 'mi.nombre.de.evento';
    }
}
```

---

## 🔗 Coexistencia con WhatsApp API Manager

Si tienes instalados ambos paquetes (`instagram-api-manager` y `whatsapp-api-manager`) en el mismo proyecto Laravel, **no hay conflictos**. El diseño de coexistencia garantiza que:

| Aspecto | Instagram | WhatsApp |
|---|---|---|
| Canal de broadcast | `instagram-messages` | `whatsapp-messages` |
| Variable de entorno | `INSTAGRAM_BROADCAST_CHANNEL_TYPE` | `WHATSAPP_BROADCAST_CHANNEL_TYPE` |
| Configuración | `instagram.broadcast.*` | `whatsapp.broadcast.*` |
| Eventos | `Instagram\Events\*` | `Whatsapp\Events\*` |
| Procesador | `instagram.webhook.processor` | `whatsapp.webhook.processor` |
| Ruta webhook | `/instagram-webhook` | `/whatsapp-webhook` |

### Escuchar Ambos Canales Simultáneamente

```js
// En tu frontend
Echo.channel('instagram-messages')
    .listen('InstagramMessageReceived', (e) => {
        console.log('📷 Instagram:', e.data);
    });

Echo.channel('whatsapp-messages')
    .listen('MessageReceived', (e) => {
        console.log('💬 WhatsApp:', e.data);
    });
```

### Canales Personalizados Compartidos

Si activas `INSTAGRAM_CUSTOM_CHANNELS=true` y `WHATSAPP_CUSTOM_CHANNELS=true`, ambos paquetes dejarán de cargar sus rutas de canal. Tú defines TODO en `routes/channels.php`:

```php
// routes/channels.php

use Illuminate\Support\Facades\Broadcast;

// Canales de Instagram
Broadcast::channel('instagram-messages', function ($user) {
    return $user !== null && $user->hasPermission('instagram.read');
});

// Canales de WhatsApp
Broadcast::channel('whatsapp-messages', function ($user) {
    return $user !== null && $user->hasPermission('whatsapp.read');
});
```

---

## 🐛 Solución de Problemas

### Los eventos no se transmiten

1. Verifica que `BROADCAST_CONNECTION=reverb` esté configurado en tu `.env`
2. Asegúrate de que el servidor Reverb esté corriendo: `php artisan reverb:start`
3. Revisa que la cola de Laravel esté procesando: `php artisan queue:work`
4. Verifica los logs en `storage/logs/instagram.log` — los eventos fallidos se registran como warnings

### Error: "Undefined type ShouldBroadcast"

Asegúrate de que el paquete `laravel/framework` esté correctamente instalado. `ShouldBroadcast` es parte de `illuminate/broadcasting` que viene incluido en Laravel.

### Los canales privados no funcionan

1. Asegúrate de que `INSTAGRAM_BROADCAST_CHANNEL_TYPE=private`
2. Publica y personaliza las rutas de canal: `php artisan vendor:publish --tag=instagram-channels`
3. Verifica que tu frontend esté enviando el token de autenticación correctamente en la conexión Echo

### El procesador personalizado no se carga

1. Verifica que la clase exista y sea autocargable por Composer
2. Asegúrate de que implemente `WebhookProcessorInterface`
3. Limpia la caché de configuración: `php artisan config:clear`

---

## 📋 Resumen de Eventos

| Evento | Canal | Se Dispara Cuando... |
|---|---|---|
| `InstagramMessageReceived` | `instagram-messages` | Llega un mensaje de texto/imagen/video/audio/adjunto/quick reply |
| `InstagramPostbackReceived` | `instagram-messages` | El usuario hace clic en un botón CTA |
| `InstagramReactionReceived` | `instagram-messages` | El usuario reacciona a un mensaje con emoji |
| `InstagramOptinReceived` | `instagram-messages` | El usuario acepta recibir mensajes (opt-in) |
| `InstagramReferralReceived` | `instagram-messages` | El usuario llega por enlace de referencia (ig.me, anuncio) |
| `InstagramReadReceived` | `instagram-messages` | El usuario lee un mensaje que tú enviaste |
| `InstagramMessageEdited` | `instagram-messages` | El usuario edita un mensaje previamente enviado |

---

[◄◄ Webhooks y Eventos](07-webhooks.md) | [▲ Tabla de contenido](00-tabla-de-contenido.md)
