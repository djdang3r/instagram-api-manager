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

## 📋 Configuration Reference

| Config Key | Tipo | Default | Descripción |
|------------|------|---------|-------------|
| `instagram.publishing.max_caption_length` | `int` | `2200` | Máximo de caracteres en caption (Meta) |
| `instagram.publishing.max_hashtags` | `int` | `30` | Máximo de hashtags (Meta) |
| `instagram.publishing.max_mentions` | `int` | `20` | Máximo de @tags (Meta) |
| `instagram.publishing.max_image_size` | `int` | `8388608` | 8MB en bytes (Meta) |
| `instagram.publishing.max_video_size` | `int` | `1073741824` | 1GB en bytes (Meta) |
| `instagram.publishing.allowed_formats` | `array` | `[JPEG, PNG, MP4, MOV]` | Formatos permitidos |
| `instagram.publishing.use_resumable_upload` | `bool` | `false` | Upload resumible para videos >1GB |
| `instagram.publishing.check_status_interval` | `int` | `5` | Segundos entre polls de status |

## ❓ FAQ

**¿Por qué mi publicación falla con error 2207042?**
Superaste el rate limit de 50 publicaciones por 24h. Esperá o distribuí en el tiempo. Configurá `instagram:publish --throttle`.

**¿Las publicaciones en carrusel cuentan como 1 o N?**
Cuentan como 1 (Meta). Pero el límite de 50/24h aplica igual.

**¿Puedo programar publicaciones?**
Sí, vía `publishScheduled()` con timestamp futuro. El comando `instagram:publish-scheduled` procesa la cola.

**¿Las stories expiran automáticamente?**
Sí, después de 24h. El campo `expires_at` se setea al publicar.

## 🚀 Ejemplo: Publicar Carrusel Completo

```php
use ScriptDevelop\InstagramApiManager\Facades\Instagram;
use Illuminate\Support\Facades\Storage;

$account = InstagramModelResolver::instagram_business_account()
    ->where('instagram_business_account_id', $igUserId)
    ->firstOrFail();

$publishing = Instagram::publishing()
    ->withAccessToken($account->access_token);

// Paso 1: Crear containers para cada item (5 imágenes)
$containerIds = [];
foreach (range(1, 5) as $i) {
    $imageUrl = Storage::url("posts/slide-{$i}.jpg");
    $result = $publishing->createContainer(
        mediaType: 'IMAGE',
        imageUrl: $imageUrl,
        isCarouselItem: true  // Marca como item de carrusel
    );
    $containerIds[] = $result['id'];
}

// Paso 2: Crear container de carrusel con los items
$carouselContainer = $publishing->createCarouselContainer(
    children: $containerIds,
    caption: '🌟 Nueva colección primavera 2026! Link en bio 🌟 #moda #primavera2026',
    locationId: 'optional_location_id'
);

// Paso 3: Publicar
$published = $publishing->publishContainer($carouselContainer['id']);

return [
    'media_id' => $published['id'],
    'permalink' => $published['permalink'],
];
```

## 📊 Comando de Publicación Programada

```php
// app/Console/Kernel.php
$schedule->call(function () {
    Artisan::call('instagram:publish-scheduled');
})->everyFiveMinutes();
```

## 🎯 Estrategia de Contenido

| Frecuencia | Tipo | Engagement típico |
|------------|------|-------------------|
| 1-2/día | Feed (imagen/carrusel) | 2-4% |
| 3-5/semana | Reel | 5-10% (mayor alcance) |
| 1-2/día | Story | 8-12% (ephemeral) |
| 2-3/semana | IGTV (legacy) | 3-6% |

## 🛠️ Troubleshooting

**Error 2207042: Publishing rate limit exceeded**
Superaste 50 posts en 24h. Esperá o distribuí con cola de scheduling.

**Container status_code = EXPIRED**
El container no se publicó dentro de 24h. Crear uno nuevo.

**Media download too slow (2207003)**
Tu servidor es lento. Considera usar `instagram.publishing.use_resumable_upload = true`.

**Caption rechazado con error 2207040**
Más de 30 hashtags o 20 @tags. Reducir.

---

[◄◄ Menciones](10-menciones.md) | [Insights ►►](12-insights.md)

## 🧪 Testing desde Proyecto Laravel Externo

Este paquete NO incluye tests internos (se testean desde un proyecto Laravel externo). Aquí está el patrón recomendado usando Testbench + PHPUnit.


## 📊 Status de Container: ciclo de vida

```
IN_PROGRESS  →  FINISHED  →  PUBLISHED
     │              │
     │              └── ERROR (con error_code en response)
     │
     └── EXPIRED (>24h sin publish)
```

## 🔄 Polling automático

El servicio `getMediaStatus()` implementa polling con backoff exponencial:
- Primer check: 1s después del upload
- Si IN_PROGRESS: espera 3s
- Si sigue IN_PROGRESS: espera 9s
- Max 5 intentos (max ~30s de espera)
- Después retorna error si sigue en progreso

## 📤 Upload Resumible (videos >1GB)

Para videos grandes, usar upload chunked:

```php
$result = $publishing->createContainer(
    mediaType: 'REELS',
    videoUrl: 'https://example.com/large-video.mp4',  // Debe soportar Range requests
    useResumableUpload: true  // El servicio hace POST a /ig-api-upload/ con chunks
);
```

## 🎬 Diferencias entre tipos de media

| Tipo | `media_type` | Límites | Notas |
|------|--------------|---------|-------|
| Image | `IMAGE` | 8MB, JPEG/PNG | Single image |
| Video | `REELS` | 1GB, MP4/MOV | Hasta 90s (reel) o 60min (video) |
| Carousel | `CAROUSEL` | 10 items, mix image/video | Cada item <= 8MB/1GB |
| Story | `STORIES` | 8MB image, 1GB video | Solo 24h, sin caption |

