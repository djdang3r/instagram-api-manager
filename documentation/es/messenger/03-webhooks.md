# 📡 Webhook de Messenger

## 🌐 URL del Webhook

El webhook de Messenger está disponible en:

```
POST /facebook-webhook
GET  /facebook-webhook   (verificación)
```

> ⚠️ **Separación**: El webhook de Messenger es independiente del de Instagram (`/instagram-webhook`). Esto facilita la depuración y permite configurar tokens distintos.

## ✅ Verificación del Webhook (GET)

Cuando configurás el webhook en el dashboard de Meta, Facebook envía una solicitud GET para verificar que tu servidor responde correctamente:

```
GET /facebook-webhook?hub.mode=subscribe&hub.challenge=TEST123&hub.verify_token=tu_token
```

El paquete valida que `hub.verify_token` coincida con `FACEBOOK_WEBHOOK_VERIFY_TOKEN` en tu `.env` y retorna `hub.challenge`:

```env
FACEBOOK_WEBHOOK_VERIFY_TOKEN=mi_token_super_secreto
```

### Configuración en Meta Dashboard

1. Ir a [Meta for Developers](https://developers.facebook.com/) → Tu App → Productos → Messenger → Configuración de Webhooks
2. URL de callback: `https://tu-dominio.com/facebook-webhook`
3. Token de verificación: el mismo valor que `FACEBOOK_WEBHOOK_VERIFY_TOKEN`
4. Suscribir a los siguientes campos:
   - `messages` — Mensajes entrantes
   - `message_echoes` — Confirmación de mensajes enviados
   - `messaging_postbacks` — Postbacks de botones
   - `message_reactions` — Reacciones a mensajes
   - `messaging_seen` — Read receipts
   - `messaging_referral` — Referrals (m.me links)
   - `messaging_optins` — Opt-ins
   - `message_edits` — Ediciones de mensajes

## 📨 Payload Entrante (POST)

Cuando un usuario envía un mensaje a tu página, Meta envía un POST con este formato:

```json
{
  "object": "page",
  "entry": [
    {
      "id": "10234567890123",
      "time": 1458692752478,
      "messaging": [
        {
          "sender": {
            "id": "9876543210987"
          },
          "recipient": {
            "id": "10234567890123"
          },
          "timestamp": 1458692752478,
          "message": {
            "mid": "mid.1458692752478:abc123",
            "text": "Hola, necesito ayuda"
          }
        }
      ]
    }
  ]
}
```

### Tipos de mensaje entrante

#### Texto
```json
"message": {
  "mid": "mid.xxx",
  "text": "Hola!"
}
```

#### Con Adjunto (imagen, audio, video, archivo)
```json
"message": {
  "mid": "mid.xxx",
  "attachments": [
    {
      "type": "image",
      "payload": {
        "url": "https://cdn.fb.com/imagen.jpg"
      }
    }
  ]
}
```

#### Quick Reply Response
```json
"message": {
  "mid": "mid.xxx",
  "text": "Rojo",
  "quick_reply": {
    "payload": "COLOR_RED"
  }
}
```

#### Postback
```json
"postback": {
  "mid": "mid.xxx",
  "title": "Confirmar",
  "payload": "CONFIRM_ORDER"
}
```

#### Reacción
```json
"reaction": {
  "mid": "mid.xxx",
  "reaction": "like",
  "emoji": "❤️",
  "action": "react"
}
```

#### Read Receipt
```json
"read": {
  "mid": "mid.xxx",
  "watermark": 1458692752478
}
```

#### Referral (m.me link)
```json
"referral": {
  "ref": "campana_verano",
  "source": "SHORTLINK",
  "type": "OPEN_THREAD"
}
```

#### Message Echo (mensaje enviado por la página)
```json
"message": {
  "is_echo": true,
  "app_id": 123456789,
  "mid": "mid.xxx",
  "text": "Respuesta automática"
}
```

## 🔄 Diferencia con el Webhook de Instagram

| | Instagram | Messenger |
|---|---|---|
| **URL** | `/instagram-webhook` | `/facebook-webhook` |
| **`object`** | `"instagram"` | `"page"` |
| **Recipient ID** | IGSID (Instagram-scoped) | PSID (Page-scoped) |
| **Sender ID** | IGSID | PSID |
| **Page ID** | `entry[].id` = IG Business Account ID | `entry[].id` = Facebook Page ID |
| **Echo** | `is_echo: true` | `is_echo: true` + `app_id` + `metadata` |

## 🛡️ Seguridad

> ⚠️ **Importante**: Para entornos de producción, asegurate de:
> 1. Usar HTTPS para la URL del webhook
> 2. Configurar `FACEBOOK_WEBHOOK_VERIFY_TOKEN` con un valor aleatorio y seguro
> 3. La ruta debe estar excluida de CSRF (el wizard de instalación lo hace automáticamente)

## 📡 Nuevos Webhook Fields (2026)

Desde la versión 1.1.0, el paquete soporta los siguientes campos adicionales:

| Field | Trigger | Payload clave |
|-------|---------|---------------|
| `message_edit` | Usuario edita un mensaje enviado | `message.mid`, `message.text` (nuevo) |
| `inbox_labels` | Label asignado/removido de un usuario | `inbox_labels.label_ids[]` |
| `standby` | Mensaje recibido por secondary receiver (handover) | Similar a `messages` |
| `messaging_account_linking` | Usuario completa account linking | `account_linking.status` |
| `messaging_feedback` | Usuario reporta/bloquea | `action` (block/unblock/report) |

## 🆕 `message_edit` Ejemplo

```json
{
  "object": "page",
  "entry": [{
    "messaging": [{
      "sender": {"id": "PSID"},
      "recipient": {"id": "PAGE_ID"},
      "message_edit": {
        "mid": "MESSAGE_ID",
        "text": "Texto editado por el usuario",
        "num_edits": 1
      }
    }]
  }]
}
```

El paquete persiste el texto editado en `messenger_messages.message_content` automáticamente.

## 🔒 Rate Limiting (v1.1.0+)

Las rutas de webhook tienen rate limiting configurable:

```env
FACEBOOK_WEBHOOK_RATE_LIMIT_MAX_ATTEMPTS=60
FACEBOOK_WEBHOOK_RATE_LIMIT_DECAY_MINUTES=1
```

GET (verificación): `throttle:10,1` (alineado con Meta).
POST (webhook real): configurable vía `facebook.rate_limit.*`.
