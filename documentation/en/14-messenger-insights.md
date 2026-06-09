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

## 📋 Configuration Reference

| Config Key | Tipo | Default | Descripción |
|------------|------|---------|-------------|
| `facebook.models.facebook_page` | `string` (FQCN) | `FacebookPage::class` | Modelo Eloquent |
| `facebook.insights.metrics` | `array` | `['page_messages_*']` | Métricas a sincronizar |
| `facebook.insights.period` | `string` | `day` | `day`, `week`, `month`, `days_28` |
| `facebook.insights.max_period_days` | `int` | `90` | Período máximo de query |
| `facebook.insights.sync_command_schedule` | `string` | `hourly` | Frecuencia del comando `messenger:sync-insights` |
| `facebook.insights.retention_days` | `int` | `365` | Días a retener insights antes de purgar |

## ❓ FAQ

**¿Las insights son a nivel Page o App?**
Son a nivel **Page** (cada página tiene sus propias métricas).

**¿Hay métricas a nivel conversación individual?**
Sí, pero limitadas: `page_messages_new_conversations_unique` y `page_messages_total_conversations`. Para detalle por usuario, usa `messenger_conversations` table.

**¿Por qué `page_messages_blocked_conversations` está vacío?**
Meta no retorna datos de esta métrica para Pages con <30 likes. Workaround: usar `messenger_conversations.status = 'blocked'` localmente.

**¿Cómo sé si mi Page tiene los permisos correctos?**
Necesitás `read_insights` scope. Si no lo tenés, todas las llamadas retornan 0 silenciosamente.

## 📈 Ejemplo: Dashboard de Métricas de una Página

```php
use ScriptDevelop\InstagramApiManager\Facades\Facebook;
use Carbon\Carbon;

$page = FacebookModelResolver::facebook_page()
    ->where('page_id', $pageId)
    ->firstOrFail();

$insights = Facebook::insights()
    ->withPageAccessToken($page->page_access_token);

$since = Carbon::now()->subDays(30)->format('Y-m-d');
$until = Carbon::now()->format('Y-m-d');

$messages = $insights->syncInsights(
    pageId: $page->page_id,
    since: $since,
    until: $until
);

return [
    'messages_sent_30d' => $insights->getInsights('page_messages_sent', '30d'),
    'conversations_started' => $insights->getInsights('page_messages_new_conversations_unique', '30d'),
    'blocked_conversations' => $insights->getInsights('page_messages_blocked_conversations_unique', '30d'),
    'page_views' => $insights->getInsights('page_views_total', '30d'),
];
```

## 🔄 Comandos Artisan

```bash
# Sincronizar insights de una página
php artisan messenger:sync-insights --page=PAGE_ID

# Sincronizar todas las páginas activas
php artisan messenger:sync-insights --all

# Período custom (7 días)
php artisan messenger:sync-insights --all --since=7days
```

## 📊 Comando Programado

```php
// app/Console/Kernel.php
$schedule->command('messenger:sync-insights --all')
    ->dailyAt('03:00')
    ->withoutOverlapping();
```

## 🎯 Métricas Clave para Bots de Messenger

| Métrica | KPI saludable | Notas |
|---------|--------------|-------|
| `block_rate` | < 2% | blocked / total conversations |
| `response_time` | < 5min | Promedio de tiempo de respuesta del bot |
| `conversation_completion` | > 60% | % de conversaciones con respuesta del usuario |
| `cta_clicks` | > 5% | Clics en botones CTA / mensajes enviados |

## 🛠️ Troubleshooting

**Error 17: Rate limit exceeded**
Meta limita a 200 calls/hour por page. Implementá cache o reduce frequency.

**Métricas vacías después de actualizar token**
Las métricas demoran 24-48h en aparecer después de cambios de scope.

**Discrepancia entre Meta Business Suite y la API**
Normal. La API a veces excluye datos de spam o bots. Diferencia típica: 3-5%.

---

[◄◄ Notificaciones](06-notificaciones.md)

## 🧪 Testing desde Proyecto Laravel Externo

Este paquete NO incluye tests internos (se testean desde un proyecto Laravel externo). Aquí está el patrón recomendado usando Testbench + PHPUnit.


## 📚 Response Format

```json
{
  "data": [
    {
      "name": "page_messages_total",
      "period": "day",
      "values": [
        {"value": 42, "end_time": "2026-06-05T07:00:00+0000"},
        {"value": 38, "end_time": "2026-06-06T07:00:00+0000"}
      ],
      "title": "Daily Messages Sent",
      "description": "Daily total of messages sent from your page",
      "id": "page_messages_total"
    }
  ],
  "paging": {
    "previous": "https://graph.facebook.com/v25.0/...&since=...&until=...",
    "next": "https://graph.facebook.com/v25.0/...&since=...&until=..."
  }
}
```

## 🆚 Métricas Disponibles por Tier

| Métrica | Basic Access | Advanced Access |
|---------|--------------|------------------|
| `page_messages_total` | ✅ | ✅ |
| `page_messages_new_conversations` | ✅ | ✅ |
| `page_messages_blocked_conversations` | ❌ | ✅ |
| `page_messages_reported_conversations` | ❌ | ✅ |
| `page_views_total` | ✅ | ✅ |
| `page_impressions` | ✅ | ✅ |
| `page_fan_adds_unique` | ❌ | ✅ |
| `page_fan_removes_unique` | ❌ | ✅ |

