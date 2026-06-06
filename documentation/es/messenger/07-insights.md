[◄◄ Notificaciones](06-notificaciones.md)
[▲ Tabla de contenido](00-tabla-de-contenido.md)

# 📊 Insights de Messenger

Obtené métricas de tus conversaciones: mensajes totales, nuevas conversaciones y conversaciones bloqueadas.

## 📋 Requisitos

- **Permiso**: `pages_read_engagement`
- **Token**: Page Access Token
- Los datos están disponibles con ~24h de retraso

## 🚀 Uso

```php
use ScriptDevelop\InstagramApiManager\Facades\Facebook;

$insights = Facebook::insights()
    ->withPageAccessToken('EAAxxx...');

// Mensajes totales en un período
$total = $insights->getTotalMessages(
    pageId: 'PAGE_ID',
    since: '2026-05-01',
    until: '2026-05-26'
);

// Nuevas conversaciones iniciadas
$new = $insights->getNewConversations(
    pageId: 'PAGE_ID',
    since: '2026-05-01',
    until: '2026-05-26'
);

// Conversaciones bloqueadas/reportadas
$blocked = $insights->getBlockedConversations(
    pageId: 'PAGE_ID',
    since: '2026-05-01',
    until: '2026-05-26'
);
```

## 📊 Tabla de Métodos

| Método | Métrica de Meta | Descripción |
|--------|----------------|-------------|
| `getTotalMessages` | `page_messages_total` | Total de mensajes enviados/recibidos |
| `getNewConversations` | `page_messages_new_conversations` | Nuevas conversaciones iniciadas |
| `getBlockedConversations` | `page_messages_blocked_conversations` | Conversaciones bloqueadas/reportadas |

## 📅 Formato de Fechas

Usar formato `YYYY-MM-DD`. El período máximo es de 90 días.

```php
$since = now()->subDays(30)->format('Y-m-d');
$until = now()->format('Y-m-d');
```

---

[◄◄ Notificaciones](06-notificaciones.md)
