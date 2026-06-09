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

## 📋 Configuration Reference

| Config Key | Tipo | Default | Descripción |
|------------|------|---------|-------------|
| `instagram.models.instagram_comment` | `string` (FQCN) | `InstagramComment::class` | Modelo Eloquent |
| `instagram.webhook.events.mentions` | `bool` | `true` | Procesar webhooks de @mentions |
| `instagram.mentions.max_age_days` | `int` | `7` | Solo procesar menciones de los últimos N días |

## ❓ FAQ

**¿Las menciones en Stories son procesadas?**
No. Meta no expone webhooks para menciones en Stories por privacidad. Solo menciones en comments y captions.

**¿Puedo responder a una mención con un DM privado?**
Sí, `replyToMention()` envía un comentario público. Para DM privado desde mención, usa `Instagram::message()->sendPrivateReply()` (ver [Instagram::comment() private_replies](09-comentarios.md)).

**¿Las menciones de mi cuenta en media de OTRAS cuentas funcionan?**
Sí, pero solo si la cuenta que te mencionó es pública. Para cuentas privadas, Meta no expone los datos.

## 🚀 Ejemplo: Monitoreo y Respuesta Automática

```php
use ScriptDevelop\InstagramApiManager\Facades\Instagram;
use ScriptDevelop\InstagramApiManager\Events\InstagramMentionReceived;

class MentionAutoResponder
{
    public function handle(InstagramMentionReceived $event): void
    {
        $mention = $event->mention;

        // Filtrar menciones de cuentas pequeñas (no responder a trolls/bots)
        $mentioner = $mention->from;
        if ($mentioner['followers_count'] < 100) {
            return;
        }

        // Responder solo si la mención es positiva (requiere NLP o filtro manual)
        $positiveKeywords = ['genial', 'recomiendo', 'excelente', 'me encanta'];
        $text = strtolower($mention->text);
        if (collect($positiveKeywords)->contains(fn($kw) => str_contains($text, $kw))) {
            Instagram::comment()
                ->withAccessToken(config('services.instagram.system_token'))
                ->replyToComment(
                    commentId: $mention->id,
                    message: "¡Gracias por mencionarnos @{$mentioner['username']}! 🙏💜"
                );
        }
    }
}

// Registrar listener en EventServiceProvider
protected $listen = [
    'ScriptDevelop\InstagramApiManager\Events\InstagramMentionReceived' => [
        MentionAutoResponder::class,
    ],
];
```

## 📊 Comando de Sync

```bash
# Sincronizar menciones de los últimos 7 días
php artisan instagram:sync-mentions --since=7days

# Solo menciones no respondidas
php artisan instagram:sync-mentions --unanswered
```

## 🎯 Casos de Uso

- **Customer service**: responder a menciones con preguntas sobre productos
- **Lead generation**: capturar usuarios que mencionan "alguien que recomiende X"
- **Brand monitoring**: trackear sentiment agregado en menciones
- **Crisis management**: detección temprana de quejas virales

## 🛠️ Troubleshooting

**Menciones en stories no se procesan**
Meta no expone webhooks para story mentions. Usá polling manual o third-party service.

**Reply a mención falla con error 2207008**
El reply excede 2200 caracteres o contiene caracteres inválidos. Validar antes de enviar.

**Webhook se dispara múltiples veces para la misma mención**
Idempotency: verificar `instagram_mention_id` único antes de procesar (el servicio ya lo hace).

---

[◄◄ Comentarios](09-comentarios.md) | [Publicación ►►](11-publicacion.md)

## 🧪 Testing desde Proyecto Laravel Externo

Este paquete NO incluye tests internos (se testean desde un proyecto Laravel externo). Aquí está el patrón recomendado usando Testbench + PHPUnit.


## 📚 Referencia de Webhook `mentions`

```json
{
  "object": "instagram",
  "entry": [{
    "id": "IG_ACCOUNT_ID",
    "time": 1620000000,
    "changes": [{
      "field": "mentions",
      "value": {
        "comment_id": "COMMENT_ID",
        "media_id": "MEDIA_ID",
        "from": {"id": "USER_ID", "username": "user"}
      }
    }]
  }]
}
```

## 🆚 Diferencia con Comments

| Aspecto | Comments | Mentions |
|---------|----------|----------|
| Trigger | Alguien comenta en tu post | Alguien @menciona tu cuenta |
| Scope | Todos los comments | Solo los que te mencionan |
| Privacidad | Solo posts públicos | Posts públicos Y privados (si te mencionan) |
| Endpoint | `/{ig-media-id}/comments` | `/{ig-user-id}?fields=mentioned_comment` |

## 🔄 Conversión de Mention a Comment Reply

Si querés responder a una mención como si fuera un comment reply público:

```php
Instagram::comment()
    ->withAccessToken($account->access_token)
    ->replyToComment(
        commentId: $mention->comment_id,  // El comment original donde te mencionaron
        message: 'Tu respuesta aquí'
    );
```

