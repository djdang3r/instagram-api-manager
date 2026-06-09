[◄◄ Publicación](11-publicacion.md)
[▲ Tabla de contenido](00-tabla-de-contenido.md)

# 📊 Insights — Instagram

Obtené métricas de tu cuenta y publicaciones: alcance, impresiones, visitas al perfil, interacciones.

## 📋 Requisitos

- **Permiso**: `instagram_manage_insights`
- **Token**: Access token de cuenta Instagram Business

## 🚀 Uso

```php
use ScriptDevelop\InstagramApiManager\Facades\Instagram;

$insights = Instagram::insights()
    ->withAccessToken($account->access_token);
```

### Métricas de Cuenta

```php
$accountMetrics = $insights->getAccountInsights(
    igUserId: $account->instagram_business_account_id,
    metrics: ['impressions', 'reach', 'profile_views', 'follower_count'],
    period: 'day'
);
```

### Métricas de Publicación

```php
$mediaMetrics = $insights->getMediaInsights(
    mediaId: 'MEDIA_ID',
    metrics: ['impressions', 'reach', 'engagement', 'saved']
);
```

### Métricas disponibles

| Métrica | Descripción |
|---------|-------------|
| `impressions` | Veces que se mostró |
| `reach` | Usuarios únicos alcanzados |
| `profile_views` | Visitas al perfil |
| `follower_count` | Seguidores |
| `email_contacts` | Clics en email |
| `phone_call_clicks` | Clics en llamar |
| `website_clicks` | Clics en sitio web |
| `get_directions_clicks` | Clics en cómo llegar |

## 📊 Tabla de Métodos

| Método | Descripción | Retorno |
|--------|-------------|---------|
| `getAccountInsights` | Métricas de cuenta | `?array` |
| `getMediaInsights` | Métricas de publicación | `?array` |

## 📋 Configuration Reference

| Config Key | Tipo | Default | Descripción |
|------------|------|---------|-------------|
| `instagram.insights.metrics_account` | `array` | `['reach', 'impressions', 'profile_views', 'follower_count']` | Métricas de cuenta |
| `instagram.insights.metrics_media` | `array` | `['likes', 'comments', 'saves', 'reach', 'views']` | Métricas de media |
| `instagram.insights.period` | `string` | `day` | `day`, `week`, `days_28`, `lifetime` |
| `instagram.insights.since_default` | `int` | `30` | Días hacia atrás si no se especifica `since` |
| `instagram.insights.cache.enabled` | `bool` | `false` | Cachear respuestas (reduce rate limit) |
| `instagram.insights.cache.ttl` | `int` | `3600` | TTL en segundos |

## ❓ FAQ

**¿Las métricas demográficas están disponibles?**
Sí, pero solo con `metric_type=total_value` y `timeframe` específico. Requieren cuenta con +100 followers.

**¿Cuánto tarda Meta en actualizar las insights?**
Hasta 48 horas. Para datos en tiempo real, no hay API pública.

**¿Por qué `impressions` retorna 0?**
La métrica `impressions` fue deprecada en Graph API v22.0. Usá `reach` en su lugar.

**¿Puedo exportar insights a CSV?**
No built-in. Usá el método `getAccountInsights()` y procesá el array con `fputcsv()`.

## 📈 Ejemplo Completo: Dashboard de Métricas

```php
use ScriptDevelop\InstagramApiManager\Facades\Instagram;
use Carbon\Carbon;

$account = InstagramModelResolver::instagram_business_account()
    ->where('instagram_business_account_id', $igUserId)
    ->firstOrFail();

$insights = Instagram::insights()
    ->withAccessToken($account->access_token);

// Métricas de los últimos 7 días
$since = Carbon::now()->subDays(7)->format('Y-m-d');
$until = Carbon::now()->format('Y-m-d');

$reach = $insights->getAccountInsights(
    igUserId: $account->instagram_business_account_id,
    metrics: ['reach'],
    period: 'day',
    since: $since,
    until: $until
);

// Top 5 publicaciones por engagement
$topMedia = $account->media()
    ->orderByDesc('likes_count')
    ->limit(5)
    ->get()
    ->map(function ($media) use ($insights) {
        $metrics = $insights->getMediaInsights(
            mediaId: $media->media_id,
            metrics: ['engagement', 'saved', 'reach']
        );
        return [
            'media_id' => $media->media_id,
            'caption' => $media->caption,
            'engagement' => $metrics['engagement'] ?? 0,
            'saved' => $metrics['saved'] ?? 0,
            'reach' => $metrics['reach'] ?? 0,
        ];
    });

return view('dashboard.instagram', compact('reach', 'topMedia'));
```

## 🔄 Comandos Artisan

```bash
# Sincronizar insights de una cuenta específica
php artisan instagram:sync-insights --account=1234567890

# Sincronizar TODAS las cuentas
php artisan instagram:sync-insights --all

# Solo métricas de los últimos 7 días
php artisan instagram:sync-insights --since=7days

# Con output verbose
php artisan instagram:sync-insights --all -v
```

## 📊 Comando Programado

Agregá en `app/Console/Kernel.php`:
```php
$schedule->command('instagram:sync-insights --all')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer();
```

## 🎯 Interpretación de Métricas

| Métrica | Valor bueno | Valor excelente | Notas |
|---------|-------------|-----------------|-------|
| `engagement_rate` | > 1% | > 3% | (likes+comments+saves) / reach |
| `reach_rate` | > 20% | > 50% | reach / followers |
| `save_rate` | > 2% | > 5% | saves / reach (contenido de valor) |
| `video_completion` | > 30% | > 60% | Para reels, % que ve completo |

## 🛠️ Troubleshooting

**Problema: `getAccountInsights` retorna `null`**
- Verificar que el token no esté expirado (usar `Instagram::account()->isTokenExpired($account)`)
- Verificar scope `instagram_manage_insights`
- Verificar que la cuenta sea Business o Creator (no Personal)

**Problema: Métricas aparecen vacías**
- Meta tiene delay de hasta 48h en actualizar
- Cuentas nuevas (< 24h) no tienen insights aún

**Problema: Error 17 (rate limit)**
- Implementar cache con `instagram.insights.cache.enabled = true`
- Reducir frecuencia de sync con `instagram.insights.since_default = 90` (más días por llamada)

---

[◄◄ Publicación](11-publicacion.md)

## 🧪 Testing desde Proyecto Laravel Externo

Este paquete NO incluye tests internos (se testean desde un proyecto Laravel externo). Aquí está el patrón recomendado usando Testbench + PHPUnit.


## 📊 Métricas Cross-Platform

Para comparar performance entre Instagram y Messenger:

```php
use ScriptDevelop\InstagramApiManager\Facades\Instagram;
use ScriptDevelop\InstagramApiManager\Facades\Facebook;

$igReach = Instagram::insights()->getAccountInsights(
    igUserId: $igUserId,
    metrics: ['reach'],
    period: 'days_28'
);

$fbReach = Facebook::insights()->getInsights(
    pageId: $pageId,
    metrics: ['page_views'],
    period: 'days_28'
);

// Calcular engagement rate combinado
$engagementRate = (
    ($igReach['reach'] ?? 0) + ($fbReach['page_views'] ?? 0)
) / max($totalFollowers, 1) * 100;
```

## 🔮 Predicción con históricos

```php
// Predecir reach del próximo post basado en históricos
$pastPosts = $account->media()
    ->where('timestamp', '>=', now()->subDays(90))
    ->get();

$avgReach = $pastPosts->avg(function ($post) use ($insights) {
    $metrics = $insights->getMediaInsights($post->media_id, ['reach']);
    return $metrics['reach'] ?? 0;
});

// Para un post nuevo, esperar ~$avgReach ± 20%
```

## 🔄 Comparación con Facebook Insights

Las métricas de Instagram son **diferentes** a las de Facebook. No mezcles:

| Instagram | Facebook equivalente | Notas |
|-----------|---------------------|-------|
| `reach` | `page_impressions_unique` | Concepto similar pero plataformas distintas |
| `impressions` (deprecada) | `page_impressions` | Facebook aún la soporta |
| `profile_views` | `page_views` | Nombres similares, significados distintos |
| `follower_count` | `page_fans` | Diferentes unidades |

