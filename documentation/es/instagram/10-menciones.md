[◄◄ Comentarios](09-comentarios.md)
[▲ Tabla de contenido](00-tabla-de-contenido.md)
[Publicación ►►](11-publicacion.md)

# 📢 Menciones — Instagram

Detectá y respondé cuando otros usuarios @mencionan tu cuenta de Instagram en comentarios y publicaciones.

## 📋 Requisitos

- **Permisos**: `instagram_manage_comments`, `instagram_basic`
- **Webhook**: Suscribirse al campo `mentions` en Meta Dashboard

## 🚀 Uso

```php
use ScriptDevelop\InstagramApiManager\Facades\Instagram;

$mentions = Instagram::comment()
    ->withAccessToken($account->access_token);

// Obtener comentarios donde te @mencionaron
$comments = $mentions->getMentionedComments($account->instagram_business_account_id);

// Obtener publicaciones donde te @mencionaron en el caption
$media = $mentions->getMentionedMedia($account->instagram_business_account_id);

// Responder a una @mención
$mentions->replyToMention(
    igUserId: $account->instagram_business_account_id,
    commentId: 'COMMENT_ID',
    message: '¡Gracias por mencionarnos! ¿En qué podemos ayudarte?'
);
```

## 📡 Webhook de Menciones

Payload que envía Meta cuando alguien te @menciona:

```json
{
  "object": "instagram",
  "entry": [{
    "id": "IG_USER_ID",
    "time": 1710200000000,
    "changes": [{
      "field": "mentions",
      "value": {
        "comment_id": "12345",
        "media_id": "67890",
        "from": {"id": "IGSID", "username": "usuario123"},
        "text": "@miempresa qué opinan de este producto?"
      }
    }]
  }]
}
```

## 📊 Tabla de Métodos

| Método | Descripción | Retorno |
|--------|-------------|---------|
| `getMentionedComments(string)` | Comentarios con @menciones | `?array` |
| `getMentionedMedia(string)` | Media con @mención en caption | `?array` |
| `replyToMention(string, string, string)` | Responder a @mención | `?array` |

## ❌ Errores Comunes

| Error | Causa | Solución |
|-------|-------|----------|
| Sin resultados | No hay @menciones recientes | Normal si nadie te mencionó |
| Permiso denegado | Sin `instagram_manage_comments` | Solicitar Advanced Access |

---

[◄◄ Comentarios](09-comentarios.md) | [Publicación ►►](11-publicacion.md)
