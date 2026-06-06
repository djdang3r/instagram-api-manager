[◄◄ Eventos](08-eventos-tiempo-real.md)
[▲ Tabla de contenido](00-tabla-de-contenido.md)
[Menciones ►►](10-menciones.md)

# 💬 Moderación de Comentarios — Instagram

Gestioná comentarios en las publicaciones de Instagram: obtenelos, respondé, ocultá, eliminá y controlá la sección de comentarios.

## 📋 Requisitos

- **Permisos**: `instagram_manage_comments`, `instagram_basic`
- **Token**: Access token de la cuenta Instagram Business
- **App Review**: Advanced Access requerido para producción

## ⚙️ Configuración

```php
use ScriptDevelop\InstagramApiManager\Facades\Instagram;

$comments = Instagram::comment()
    ->withAccessToken($account->access_token);
```

## 📖 1. Obtener Comentarios

Obtené todos los comentarios de una publicación, incluyendo respuestas anidadas.

```php
// Obtener comentarios de un post
$result = Instagram::comment()->getComments('MEDIA_ID');

// Estructura de la respuesta:
// [
//   'data' => [
//     ['id' => '123', 'text' => '¡Qué lindo!', 'username' => 'usuario1', 'timestamp' => '...'],
//     ['id' => '456', 'text' => 'Precio?', 'username' => 'usuario2', 'replies' => ['data' => [...]]],
//   ]
// ]

// Obtener un comentario específico
$comment = Instagram::comment()->getComment('COMMENT_ID');
```

## 💬 2. Responder Comentarios

```php
// Responder a un comentario
Instagram::comment()->replyToComment(
    commentId: 'COMMENT_ID',
    message: '¡Gracias por tu comentario! 😊'
);

// Responder con información útil
Instagram::comment()->replyToComment(
    commentId: 'COMMENT_ID',
    message: 'El precio es $99. Visitá nuestro sitio: https://ejemplo.com'
);
```

## 👁️ 3. Ocultar / Mostrar Comentarios

```php
// Ocultar un comentario (sigue existiendo pero no visible públicamente)
Instagram::comment()->hideComment('COMMENT_ID');

// Volver a mostrar un comentario oculto
Instagram::comment()->unhideComment('COMMENT_ID');
```

## 🗑️ 4. Eliminar Comentarios

```php
// Eliminar completamente un comentario
Instagram::comment()->deleteComment('COMMENT_ID');
```

> ⚠️ Esta acción es irreversible. El comentario desaparece permanentemente.

## 🔒 5. Deshabilitar / Habilitar Comentarios

Controlá si una publicación acepta comentarios:

```php
// Deshabilitar comentarios en una publicación
Instagram::comment()->disableComments('MEDIA_ID');

// Volver a habilitar comentarios
Instagram::comment()->enableComments('MEDIA_ID');
```

> 💡 Útil para publicaciones sensibles o cuando querés cerrar la discusión.

## 📡 Webhook de Comentarios

Suscribite al campo `comments` en el dashboard de Meta para recibir notificaciones en tiempo real.

**Payload que envía Meta**:

```json
{
  "object": "instagram",
  "entry": [{
    "id": "IG_BUSINESS_ACCOUNT_ID",
    "time": 1710200000000,
    "changes": [{
      "field": "comments",
      "value": {
        "from": {"id": "IGSID", "username": "usuario123"},
        "comment_id": "987654321",
        "parent_id": "987654320",
        "text": "Me encanta este producto!",
        "media": {"id": "MEDIA_ID", "media_product_type": "FEED"}
      }
    }]
  }]
}
```

> ⚠️ El webhook de comentarios usa `changes[]` (NO `messaging[]`). Es un formato distinto al webhook de mensajería.

## 📩 6. Private Replies — Responder por DM

Respondé a un comentario público enviando un mensaje privado al usuario que comentó:

```php
Instagram::message()
    ->withAccessToken($account->access_token)
    ->withInstagramUserId($account->instagram_business_account_id)
    ->sendPrivateReply(
        commentId: 'COMMENT_ID',
        message: '¡Gracias por tu comentario! Te escribimos por privado para darte más info.'
    );
```

> 💡 La API de Meta automáticamente mapea el `comment_id` al IGSID del usuario. No necesitás saber quién es.

## 📊 Tabla de Métodos

| Método | Descripción | Retorno |
|--------|-------------|---------|
| `getComments(string $mediaId)` | Obtener comentarios de un post | `?array` |
| `getComment(string $commentId)` | Obtener un comentario específico | `?array` |
| `replyToComment(string, string)` | Responder a un comentario | `?array` |
| `hideComment(string)` | Ocultar comentario | `bool` |
| `unhideComment(string)` | Mostrar comentario oculto | `bool` |
| `deleteComment(string)` | Eliminar comentario | `bool` |
| `disableComments(string)` | Deshabilitar comentarios en post | `bool` |
| `enableComments(string)` | Habilitar comentarios en post | `bool` |

## ❌ Errores Comunes

| Error | Causa | Solución |
|-------|-------|----------|
| Permiso denegado | Sin `instagram_manage_comments` | Solicitar Advanced Access en App Review |
| Comentario no encontrado | ID inválido o eliminado | Verificar el comment_id |
| Token inválido (190) | Token expirado | Refrescar token |

---

[◄◄ Eventos](08-eventos-tiempo-real.md) | [Menciones ►►](10-menciones.md)
