[◄◄ Cuentas](03-cuentas.md)
[▲ Tabla de contenido](00-tabla-de-contenido.md)
[Menú Persistente ►►](05-menu-persistente.md)

# 💬 Gestión de Mensajes

Envía y recibe mensajes de texto, multimedia y elementos interactivos fácilmente.

### 1. Enviar Mensajes de Texto

```php
use ScriptDevelop\InstagramApiManager\Facades\Instagram;

$result = Instagram::message()
    ->withAccessToken($account->access_token)
    ->withInstagramUserId($account->instagram_business_account_id)
    ->sendTextMessage('RECIPIENT_IGSID', 'Hola, ¿cómo podemos ayudarte?');
```

### 2. Enviar Multimedia

```php
// Enviar Imagen
$result = Instagram::message()
    ->sendImageMessage('RECIPIENT_IGSID', 'https://tu-sitio.com/imagen.jpg');

// Enviar Sticker
$result = Instagram::message()
    ->sendStickerMessage('RECIPIENT_IGSID');
```

### 3. Respuestas Rápidas (Quick Replies)

Las respuestas rápidas permiten al usuario elegir de una lista de opciones:

```php
$quickReplies = [
    ['content_type' => 'text', 'title' => 'Ventas', 'payload' => 'SALES_REQ'],
    ['content_type' => 'text', 'title' => 'Soporte', 'payload' => 'SUPPORT_REQ']
];

$result = Instagram::message()
    ->sendQuickReplies('RECIPIENT_IGSID', 'Selecciona un departamento:', $quickReplies);
```

### 4. Plantillas Genéricas

Las plantillas permiten enviar tarjetas con imágenes, subtítulos y múltiples botones:

```php
$elements = [
    [
        'title' => 'Producto Estrella',
        'image_url' => 'https://example.com/p1.jpg',
        'subtitle' => 'Mira nuestras ofertas actuales',
        'buttons' => [
            [
                'type' => 'web_url',
                'url' => 'https://example.com/shop',
                'title' => 'Ver Tienda'
            ],
            [
                'type' => 'postback',
                'title' => 'Hablar con Agente',
                'payload' => 'AGENT_REQ'
            ]
        ]
    ]
];

$result = Instagram::message()->sendGenericTemplate('RECIPIENT_IGSID', $elements);
```

### 5. Reacciones

Puedes reaccionar a mensajes específicos:

```php
$result = Instagram::message()->reactToMessage('RECIPIENT_IGSID', 'MESSAGE_ID', 'love'); // ❤️
```

---
[◄◄ Cuentas](03-cuentas.md) | [Menú Persistente ►►](05-menu-persistente.md)
