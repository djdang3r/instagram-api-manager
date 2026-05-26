# 💬 Envío y Recepción de Mensajes — Messenger

## 📤 Envío de Mensajes

Todos los métodos de envío requieren configurar las credenciales de la página:

```php
use ScriptDevelop\InstagramApiManager\Facades\Facebook;

$fb = Facebook::message()
    ->withPageAccessToken('EAAxxx...')
    ->withPageId('10234567890123');           // ID de tu página
```

### 📝 Mensaje de Texto

```php
$result = $fb->sendTextMessage(
    recipientId: '1234567890',              // PSID del destinatario
    text: '¡Hola! ¿En qué puedo ayudarte?',
    messagingType: 'RESPONSE'               // RESPONSE, UPDATE, o MESSAGE_TAG
);

// Respuesta: { message_id: "m_AG5Hz2U..." }
```

> 💡 `messagingType` por defecto es `RESPONSE`. Usá `UPDATE` para mensajes no-respuesta dentro de 24h, y `MESSAGE_TAG` para mensajes fuera de la ventana de 24h.

### 🖼️ Mensaje con Imagen

```php
// Desde URL pública
$fb->sendImageMessage(
    recipientId: '1234567890',
    imageUrl: 'https://ejemplo.com/foto.jpg',
    messagingType: 'RESPONSE'
);

// Desde archivo local (el paquete detecta automáticamente)
$fb->sendImageMessage('1234567890', new \SplFileInfo('/ruta/foto.png'), 'RESPONSE');
$fb->sendImageMessage('1234567890', '/ruta/foto.jpg', 'RESPONSE');
```

### 🎵 Mensaje con Audio

```php
// Desde URL pública
$fb->sendAudioMessage(
    recipientId: '1234567890',
    audioUrl: 'https://ejemplo.com/audio.mp3',
    messagingType: 'RESPONSE'
);

// Desde archivo local
$fb->sendAudioMessage('1234567890', '/ruta/grabacion.mp3', 'RESPONSE');
```

### 🎬 Mensaje con Video

```php
// Desde URL pública
$fb->sendVideoMessage(
    recipientId: '1234567890',
    videoUrl: 'https://ejemplo.com/video.mp4',
    messagingType: 'RESPONSE'
);

// Desde archivo local
$fb->sendVideoMessage('1234567890', new \SplFileInfo('/ruta/video.mp4'), 'RESPONSE');
```

### 📎 Mensaje con Archivo

```php
// Desde URL pública
$fb->sendFileMessage(
    recipientId: '1234567890',
    fileUrl: 'https://ejemplo.com/documento.pdf',
    messagingType: 'RESPONSE'
);

// Desde archivo local
$fb->sendFileMessage('1234567890', '/ruta/contrato.pdf', 'RESPONSE');
```

### ❤️ Sticker (Corazón)

```php
$fb->sendStickerMessage(recipientId: '1234567890');
```

### 🔘 Quick Replies

Hasta 13 botones de respuesta rápida:

```php
$fb->sendQuickReplies(
    recipientId: '1234567890',
    text: '¿Qué color preferís?',
    quickReplies: [
        [
            'content_type' => 'text',
            'title' => 'Rojo',
            'payload' => 'COLOR_RED',
        ],
        [
            'content_type' => 'text',
            'title' => 'Verde',
            'payload' => 'COLOR_GREEN',
        ],
    ],
    messagingType: 'RESPONSE'
);
```

### 🏗️ Templates

#### Generic Template (carrusel de tarjetas)

```php
$fb->sendGenericTemplate(
    recipientId: '1234567890',
    elements: [
        [
            'title' => 'Producto 1',
            'image_url' => 'https://ejemplo.com/prod1.jpg',
            'subtitle' => '$99.99',
            'buttons' => [
                ['type' => 'web_url', 'url' => 'https://...', 'title' => 'Comprar'],
            ],
        ],
        // Hasta 10 elementos
    ],
    messagingType: 'RESPONSE'
);
```

#### Button Template (texto + botones)

```php
$fb->sendButtonTemplate(
    recipientId: '1234567890',
    text: '¿Querés confirmar tu pedido?',
    buttons: [
        ['type' => 'postback', 'title' => '✅ Confirmar', 'payload' => 'CONFIRM_ORDER'],
        ['type' => 'postback', 'title' => '❌ Cancelar', 'payload' => 'CANCEL_ORDER'],
    ],
    messagingType: 'RESPONSE'
);
```

### 👁️ Read Receipt (marcar como leído)

```php
$fb->sendReadReceipt(recipientId: '1234567890');
```

### 😊 Reacción a un mensaje

```php
$fb->sendReaction(
    recipientId: '1234567890',
    messageId: 'm_AG5Hz2U...',
    reaction: '❤️'
);
```

### ↩️ Responder a un mensaje específico

```php
// Responder con texto
$fb->sendReply(
    recipientId: '1234567890',
    replyToMessageId: 'm_ORIGINAL_MESSAGE_ID',
    messagePayload: [
        'message' => ['text' => 'Respondiendo a tu mensaje anterior'],
    ],
    messagingType: 'RESPONSE'
);

// Responder con imagen desde archivo local
$fb->sendReply(
    recipientId: '1234567890',
    replyToMessageId: 'm_ORIGINAL_MESSAGE_ID',
    messagePayload: [
        'message' => [
            'attachment' => [
                'type' => 'image',
                'payload' => ['url' => '/ruta/captura.png'],
            ],
        ],
    ],
    messagingType: 'RESPONSE'
);
```

### 🖼️🖼️ Múltiples Imágenes (hasta 30)

```php
$fb->sendMultipleImages(
    recipientId: '1234567890',
    imageUrls: [
        'https://ejemplo.com/foto1.jpg',
        'https://ejemplo.com/foto2.jpg',
        'https://ejemplo.com/foto3.jpg',
    ],
    messagingType: 'RESPONSE'
);
```

### 📤 Subir Archivo Multimedia Reusable

Subí el archivo una vez y reutilizalo en múltiples mensajes:

```php
// Desde URL
$attachmentId = $fb->uploadAttachment(
    url: 'https://ejemplo.com/imagen.jpg',
    type: 'image'
);

// Desde archivo local
$attachmentId = $fb->uploadAttachment(
    url: '/ruta/local/catalogo.png',
    type: 'image'
);

// Usar en envíos posteriores
$fb->sendImageMessage(
    recipientId: '1234567890',
    imageUrl: $attachmentId,     // Se usa el attachment_id como "url"
    messagingType: 'RESPONSE'
);
```

> 💡 Formatos de media soportados: `image` (jpg, png, gif), `video` (mp4, mov), `audio` (mp3, aac), `file` (pdf, doc, etc.)

### 🏷️ Mensajes Etiquetados (fuera de 24h)

Para enviar mensajes fuera de la ventana de 24 horas, necesitás usar un Message Tag:

```php
$fb->sendTaggedMessage(
    recipientId: '1234567890',
    tag: 'CONFIRMED_EVENT_UPDATE',
    messagePayload: [
        'message' => ['text' => 'Tu pedido #1234 fue enviado 🚚'],
    ]
);
```

Tags disponibles: `ACCOUNT_UPDATE`, `CONFIRMED_EVENT_UPDATE`, `CUSTOMER_FEEDBACK`, `HUMAN_AGENT`, `POST_PURCHASE_UPDATE`.

## 📥 Recepción de Mensajes

Los mensajes entrantes se procesan automáticamente a través del webhook de Messenger y se persisten en la base de datos. Para reaccionar a mensajes entrantes, usá los eventos broadcast:

```php
// App\Listeners\HandleMessengerMessage.php
use ScriptDevelop\InstagramApiManager\Events\MessengerMessageReceived;

class HandleMessengerMessage
{
    public function handle(MessengerMessageReceived $event): void
    {
        $senderId = $event->data['sender'];
        $text = $event->data['data']['text'] ?? '';
        $message = $event->data['message'];  // Modelo MessengerMessage
        
        // El mensaje ya está guardado en BD
        // Acá podés implementar tu lógica de negocio
    }
}
```

### Estructura del payload entrante

```json
{
  "sender": "PAGE_SCOPED_ID",
  "recipient": "PAGE_ID",
  "timestamp": 1458692752478,
  "data": {
    "mid": "mid.xxx",
    "text": "Hola!",
    "attachments": [...]
  },
  "message": { /* Modelo MessengerMessage */ },
  "conversation": { /* Modelo MessengerConversation con contact eager-loaded */ }
}
```

## ⚙️ Parámetro `messaging_type`

| Valor | Cuándo usarlo | Ventana |
|-------|--------------|---------|
| `RESPONSE` | Responder a un mensaje recibido | 24 horas |
| `UPDATE` | Mensaje no-respuesta (promocional o no) | 24 horas |
| `MESSAGE_TAG` | Mensaje fuera de la ventana de 24h | Requiere tag válido |

> ⚠️ Si no especificás `messaging_type`, el paquete usa `RESPONSE` por defecto.

## 📊 Comparativa: Mensajes Instagram vs Messenger

| Tipo | Instagram | Messenger |
|------|-----------|-----------|
| Texto | ✅ | ✅ |
| Imagen | ✅ | ✅ |
| Audio | ✅ | ✅ |
| Video | ✅ | ✅ |
| Archivo | ✅ | ✅ |
| Sticker | ✅ (`like_heart`) | ✅ (`like_heart`) |
| Quick Replies | ✅ | ✅ |
| Templates | ✅ (generic, button, media) | ✅ (generic, button) |
| Reacción | ✅ | ✅ |
| Read Receipt | ✅ | ✅ |
| Reply a mensaje | ✅ | ✅ |
| Múltiples imágenes | ✅ (hasta 10) | ✅ (hasta 30) |
| Upload Attachment | ✅ | ✅ |
| Media Share (post) | ✅ `sendSharedPost` | ❌ |
| Message Tags | ❌ | ✅ |
| `messaging_type` | ❌ (no requerido) | ✅ (obligatorio) |
