# 📡 Eventos Broadcast — Messenger (Laravel Reverb)

## 🎯 Eventos Disponibles

El paquete emite 8 eventos broadcast cuando se reciben mensajes de Messenger. Cada evento se transmite por el canal `facebook-messages`.

| Evento | Gatillado por | `broadcastAs()` |
|--------|--------------|-----------------|
| `MessengerMessageReceived` | Mensaje entrante (texto, imagen, etc.) | `MessengerMessageReceived` |
| `MessengerMessageEchoReceived` | Echo de mensaje enviado por la página | `MessengerMessageEchoReceived` |
| `MessengerPostbackReceived` | Postback de botón | `MessengerPostbackReceived` |
| `MessengerReactionReceived` | Reacción a un mensaje | `MessengerReactionReceived` |
| `MessengerReadReceived` | Read receipt | `MessengerReadReceived` |
| `MessengerReferralReceived` | Referral (m.me link) | `MessengerReferralReceived` |
| `MessengerMessageEdited` | Edición de mensaje | `MessengerMessageEdited` |
| `MessengerOptinReceived` | Opt-in | `MessengerOptinReceived` |

## ⚙️ Configuración

```env
FACEBOOK_BROADCAST_CHANNEL_TYPE=public
```

- `public` — Canal público, cualquier cliente puede suscribirse
- `private` — Canal privado, requiere autenticación (ver sección abajo)

## 📦 Payload de los Eventos

Todos los eventos comparten la misma estructura de payload:

```json
{
  "sender": "PSID_DEL_REMITENTE",
  "recipient": "PAGE_ID",
  "timestamp": 1458692752478,
  "data": { /* datos específicos del evento */ },
  "message": { /* Modelo MessengerMessage | null */ },
  "conversation": { /* Modelo MessengerConversation | null */ }
}
```

## 🎧 Escuchar Eventos en Laravel

### Backend — Listener

```php
// App\Listeners\HandleMessengerMessage.php
namespace App\Listeners;

use ScriptDevelop\InstagramApiManager\Events\MessengerMessageReceived;

class HandleMessengerMessage
{
    public function handle(MessengerMessageReceived $event): void
    {
        $data = $event->data;
        
        // Datos del remitente
        $psid = $data['sender'];
        
        // Contenido del mensaje
        $text = $data['data']['text'] ?? null;
        $attachments = $data['data']['attachments'] ?? null;
        
        // Modelo persistido en BD
        $message = $data['message'];
        
        // Tu lógica de negocio aquí
        if ($text === 'ayuda') {
            // Enviar respuesta automática
        }
    }
}
```

Registrar en `EventServiceProvider`:

```php
protected $listen = [
    MessengerMessageReceived::class => [
        HandleMessengerMessage::class,
    ],
    MessengerPostbackReceived::class => [
        HandleMessengerPostback::class,
    ],
];
```

### Frontend — Laravel Echo

```javascript
import Echo from 'laravel-echo';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: false,
});

// Escuchar mensajes entrantes
window.Echo.channel('facebook-messages')
    .listen('MessengerMessageReceived', (event) => {
        console.log('Nuevo mensaje de Messenger:', event);
        
        // Mostrar notificación
        showNotification(`Nuevo mensaje de ${event.sender}`);
        
        // Actualizar UI
        appendMessage(event.data.text, event.sender);
    })
    .listen('MessengerPostbackReceived', (event) => {
        console.log('Postback recibido:', event.data.payload);
    })
    .listen('MessengerReactionReceived', (event) => {
        console.log('Reacción:', event.data.emoji);
    });
```

## 🔒 Canal Privado

Si configuraste `FACEBOOK_BROADCAST_CHANNEL_TYPE=private`, necesitás autenticación:

### Backend — Autorización del canal

El paquete ya registra la autorización en `routes/channels.php`:

```php
Broadcast::channel('facebook-messages', function ($user) {
    return $user !== null;  // Solo usuarios autenticados
});
```

### Frontend — Canal privado

```javascript
window.Echo.private('facebook-messages')
    .listen('MessengerMessageReceived', (event) => {
        // Solo usuarios autenticados reciben estos eventos
    });
```

## 📊 Comparativa: Canales Instagram vs Messenger

| | Instagram | Messenger |
|---|---|---|
| **Canal** | `instagram-messages` | `facebook-messages` |
| **Config** | `INSTAGRAM_BROADCAST_CHANNEL_TYPE` | `FACEBOOK_BROADCAST_CHANNEL_TYPE` |
| **Eventos** | 8 eventos | 8 eventos |
| **Separación** | Independiente | Independiente |

## ⚡ Laravel Reverb — Configuración completa

```bash
php artisan reverb:install
php artisan reverb:start
```

```env
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

FACEBOOK_BROADCAST_CHANNEL_TYPE=public
```

```bash
npm install laravel-echo pusher-js
```

> 💡 Para desarrollo local con ngrok: `ngrok http 8080` y usá la URL de ngrok como `REVERB_HOST`.
