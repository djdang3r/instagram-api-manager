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

---

[◄◄ Publicación](11-publicacion.md)
