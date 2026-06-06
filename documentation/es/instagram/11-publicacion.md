[◄◄ Menciones](10-menciones.md)
[▲ Tabla de contenido](00-tabla-de-contenido.md)
[Insights ►►](12-insights.md)

# 📸 Publicación de Contenido — Instagram

Publicá imágenes, videos y carruseles en Instagram desde tu aplicación.

## 📋 Requisitos

- **Permisos**: `instagram_content_publish`, `instagram_basic`
- **Token**: Access token de cuenta Instagram Business
- **App Review**: Advanced Access para producción
- La cuenta debe ser Business o Creator (no personal)

## 🚀 Uso

```php
use ScriptDevelop\InstagramApiManager\Facades\Instagram;

$publishing = Instagram::publishing()
    ->withAccessToken($account->access_token);
```

### 🖼️ Publicar Imagen

```php
$result = $publishing->publishImage(
    igUserId: $account->instagram_business_account_id,
    imageUrl: 'https://ejemplo.com/foto.jpg',
    caption: '¡Nuevo producto disponible! 🎉 #lanzamiento'
);

// Respuesta: ['media_id' => '12345', 'creation_id' => '67890']
```

### 🎬 Publicar Video/Reel

```php
$result = $publishing->publishVideo(
    igUserId: $account->instagram_business_account_id,
    videoUrl: 'https://ejemplo.com/video.mp4',
    caption: 'Así se usa nuestro producto ✨'
);
```

### 🎠 Publicar Carrusel

```php
$items = [
    ['image_url' => 'https://ejemplo.com/foto1.jpg'],
    ['image_url' => 'https://ejemplo.com/foto2.jpg'],
    ['video_url' => 'https://ejemplo.com/video.mp4'],
];

$result = $publishing->publishCarousel(
    igUserId: $account->instagram_business_account_id,
    mediaItems: $items,
    caption: 'Mira nuestra colección completa 👆'
);
```

### 📊 Verificar Estado de Publicación

```php
$status = $publishing->getMediaStatus(
    igUserId: $account->instagram_business_account_id,
    creationId: '67890'
);

// status_code: 'FINISHED', 'IN_PROGRESS', 'ERROR'
```

## 📊 Tabla de Métodos

| Método | Descripción | Retorno |
|--------|-------------|---------|
| `publishImage` | Publicar una imagen | `?array` |
| `publishVideo` | Publicar un video/reel | `?array` |
| `publishCarousel` | Publicar carrusel (imágenes+video) | `?array` |
| `getMediaStatus` | Verificar estado de publicación | `?array` |

> 💡 El proceso de publicación es en 2 pasos: crear container → publicar. El paquete lo hace automáticamente.

---

[◄◄ Menciones](10-menciones.md) | [Insights ►►](12-insights.md)
