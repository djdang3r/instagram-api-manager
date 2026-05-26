[◄◄ Cuentas](03-cuentas.md)
[▲ Tabla de contenido](00-tabla-de-contenido.md)
[Menú Persistente ►►](05-menu-persistente.md)

# 💬 Gestión de Mensajes — Instagram

Envía y recibe mensajes de texto, multimedia, plantillas, reacciones y más.

> 💡 Todos los métodos de envío requieren configurar credenciales previamente con `withAccessToken()` y `withInstagramUserId()`.

## 📝 1. Mensaje de Texto

```php
use ScriptDevelop\InstagramApiManager\Facades\Instagram;

$result = Instagram::message()
    ->withAccessToken($account->access_token)
    ->withInstagramUserId($account->instagram_business_account_id)
    ->sendTextMessage('RECIPIENT_IGSID', '¡Hola! ¿En qué puedo ayudarte?');

// Respuesta: ['response' => [...], 'message' => Model, 'conversation' => Model]
```

## 🖼️ 2. Enviar Imagen

```php
// Desde URL
Instagram::message()->sendImageMessage('RECIPIENT_IGSID', 'https://ejemplo.com/foto.jpg');

// Desde archivo local (el paquete detecta automáticamente)
Instagram::message()->sendImageMessage('RECIPIENT_IGSID', new \SplFileInfo('/ruta/foto.png'));
Instagram::message()->sendImageMessage('RECIPIENT_IGSID', '/ruta/foto.jpg');
```

## 🎵 3. Enviar Audio

```php
// Desde URL
Instagram::message()->sendAudioMessage('RECIPIENT_IGSID', 'https://ejemplo.com/audio.mp3');

// Desde archivo local
Instagram::message()->sendAudioMessage('RECIPIENT_IGSID', '/ruta/audio.mp3');
```

## 🎬 4. Enviar Video

```php
// Desde URL
Instagram::message()->sendVideoMessage('RECIPIENT_IGSID', 'https://ejemplo.com/video.mp4');

// Desde archivo local
Instagram::message()->sendVideoMessage('RECIPIENT_IGSID', new \SplFileInfo('/ruta/video.mp4'));
```

## 📎 5. Enviar Archivo

```php
// Desde URL
Instagram::message()->sendDocumentMessage('RECIPIENT_IGSID', 'https://ejemplo.com/doc.pdf');

// Desde archivo local
Instagram::message()->sendDocumentMessage('RECIPIENT_IGSID', '/ruta/documento.pdf');
```

## ❤️ 6. Enviar Sticker (Corazón)

```php
Instagram::message()->sendStickerMessage('RECIPIENT_IGSID');
```

## 🔘 7. Respuestas Rápidas (Quick Replies)

Hasta 13 botones de respuesta rápida:

```php
$quickReplies = [
    ['content_type' => 'text', 'title' => 'Ventas', 'payload' => 'SALES_REQ'],
    ['content_type' => 'text', 'title' => 'Soporte', 'payload' => 'SUPPORT_REQ'],
    ['content_type' => 'text', 'title' => 'Facturación', 'payload' => 'BILLING_REQ'],
];

Instagram::message()->sendQuickReplies('RECIPIENT_IGSID', '¿En qué área necesitás ayuda?', $quickReplies);
```

## 🏗️ 8. Plantillas

### Generic Template (carrusel de tarjetas)

Hasta 10 tarjetas con imagen, título, subtítulo y botones:

```php
$elements = [
    [
        'title' => 'Producto Estrella',
        'image_url' => 'https://ejemplo.com/producto1.jpg',
        'subtitle' => 'El más vendido del mes',
        'buttons' => [
            ['type' => 'web_url', 'url' => 'https://ejemplo.com/comprar', 'title' => 'Comprar'],
            ['type' => 'postback', 'title' => 'Más info', 'payload' => 'INFO_PROD_1'],
        ],
    ],
    [
        'title' => 'Oferta Especial',
        'image_url' => 'https://ejemplo.com/producto2.jpg',
        'subtitle' => 'Solo por tiempo limitado',
        'buttons' => [
            ['type' => 'postback', 'title' => 'Aprovechar', 'payload' => 'OFERTA_2'],
        ],
    ],
];

Instagram::message()->sendGenericTemplate('RECIPIENT_IGSID', $elements);
```

### Button Template (texto + botones)

Un mensaje de texto acompañado de hasta 3 botones:

```php
$buttons = [
    ['type' => 'postback', 'title' => '✅ Confirmar pedido', 'payload' => 'CONFIRM_ORDER'],
    ['type' => 'postback', 'title' => '❌ Cancelar', 'payload' => 'CANCEL_ORDER'],
    ['type' => 'web_url', 'url' => 'https://ejemplo.com/ayuda', 'title' => '🆘 Ayuda'],
];

Instagram::message()->sendButtonTemplate('RECIPIENT_IGSID', 'Tu pedido #1234 está listo.', $buttons);
```

### Media Share — Compartir un Post

Envía un post de Instagram como mensaje:

```php
Instagram::message()->sendSharedPost('RECIPIENT_IGSID', 'POST_ID_DE_INSTAGRAM');
```

## 😊 9. Reacciones

Reaccioná a un mensaje específico con emoji:

```php
Instagram::message()->sendReaction(
    recipientId: 'RECIPIENT_IGSID',
    messageId: 'MESSAGE_ID_A_REACCIONAR',
    reaction: '❤️'
);

// También podés usar emojis Unicode
Instagram::message()->sendReaction('RECIPIENT_IGSID', 'MESSAGE_ID', '😍');
Instagram::message()->sendReaction('RECIPIENT_IGSID', 'MESSAGE_ID', '🎉');
```

## ↩️ 10. Responder a un Mensaje

Responde a un mensaje específico dentro de la conversación:

```php
Instagram::message()->sendReply(
    recipientId: 'RECIPIENT_IGSID',
    replyToMessageId: 'MID_DEL_MENSAJE_ORIGINAL',
    messagePayload: [
        'message' => ['text' => 'Respondiendo a tu consulta...'],
    ]
);

// También funciona con imágenes
Instagram::message()->sendReply(
    recipientId: 'RECIPIENT_IGSID',
    replyToMessageId: 'MID_DEL_MENSAJE_ORIGINAL',
    messagePayload: [
        'message' => [
            'attachment' => [
                'type' => 'image',
                'payload' => ['url' => 'https://ejemplo.com/respuesta.jpg'],
            ],
        ],
    ]
);
```

## 🖼️🖼️ 11. Múltiples Imágenes (hasta 10)

Envía hasta 10 imágenes en un solo mensaje:

```php
$imagenes = [
    'https://ejemplo.com/foto1.jpg',
    'https://ejemplo.com/foto2.jpg',
    'https://ejemplo.com/foto3.jpg',
];

Instagram::message()->sendMultipleImages('RECIPIENT_IGSID', $imagenes);
```

## 📤 12. Subir Archivo Multimedia Reusable

Subí un archivo una vez y reutilizalo en múltiples mensajes sin volver a subirlo:

```php
// Subir imagen reusable
$attachmentId = Instagram::message()->uploadAttachment(
    url: 'https://ejemplo.com/catalogo.jpg',
    type: 'image'
);

// Usar en envíos posteriores (usando el attachment_id como URL)
Instagram::message()->sendImageMessage('RECIPIENT_IGSID', $attachmentId);
Instagram::message()->sendImageMessage('OTRO_RECIPIENT', $attachmentId);
```

> 💡 Los `attachment_id` expiran después de 90 días. Para uso único, enviá la URL directamente.

## 👁️ 13. Read Receipt (Marcar como Leído)

```php
Instagram::message()->sendReadReceipt('RECIPIENT_IGSID');
```

## 📥 Recepción de Mensajes

Los mensajes entrantes se procesan automáticamente vía webhook y se persisten en la base de datos. Para reaccionar, usá los eventos broadcast:

```php
// App\Listeners\HandleInstagramMessage.php
use ScriptDevelop\InstagramApiManager\Events\InstagramMessageReceived;

class HandleInstagramMessage
{
    public function handle(InstagramMessageReceived $event): void
    {
        $senderId = $event->data['sender'];
        $text = $event->data['data']['text'] ?? '';
        $message = $event->data['message']; // Modelo InstagramMessage
        
        // El mensaje ya está guardado en BD. Acá va tu lógica.
        if ($text === 'ayuda') {
            // Enviar respuesta automática
        }
    }
}
```

## 📊 Tabla de Métodos Disponibles

| Método | Descripción | Soporta archivo local |
|--------|-------------|----------------------|
| `sendTextMessage` | Texto | — |
| `sendImageMessage` | Imagen | ✅ |
| `sendAudioMessage` | Audio | ✅ |
| `sendVideoMessage` | Video | ✅ |
| `sendDocumentMessage` | Archivo/PDF | ✅ |
| `sendStickerMessage` | Sticker like_heart | — |
| `sendQuickReplies` | Respuestas rápidas | — |
| `sendGenericTemplate` | Carrusel de tarjetas | — |
| `sendButtonTemplate` | Texto + botones | — |
| `sendSharedPost` | Compartir post IG | — |
| `sendReaction` | Reacción emoji | — |
| `sendReply` | Responder mensaje | ✅ |
| `sendMultipleImages` | Hasta 10 imágenes | — |
| `uploadAttachment` | Subir media reusable | ✅ |
| `sendReadReceipt` | Marcar como leído | — |

---

[◄◄ Cuentas](03-cuentas.md) | [Menú Persistente ►►](05-menu-persistente.md)
