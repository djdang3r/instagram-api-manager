# Plan: Gestión de Archivos Multimedia — Alineado con WhatsApp

## TL;DR
> **Resumen**: Actualizar `config/instagram.php` y `config/facebook.php` con gestión de archivos multimedia al nivel del paquete WhatsApp: tamaños máximos por tipo, MIME types permitidos, rutas de almacenamiento por tipo, y validación en `sendMediaRequest()`.
> **Esfuerzo**: Medio
> **Archivos**: `config/instagram.php`, `config/facebook.php`, `ApiClient.php`
> **No rompe**: Código existente — solo agrega validación y estructura

## Contexto

El paquete WhatsApp (`scriptdevelop/whatsapp-manager`) tiene gestión completa de archivos multimedia:
- **Tamaños máximos por tipo**: image 5MB, audio 16MB, video 16MB, document 100MB, sticker 100KB
- **MIME types permitidos**: validación estricta por tipo
- **Rutas por tipo**: `storage/whatsapp/{images,audios,documents,videos,stickers}`
- **Validación pre-upload**: verifica tamaño y MIME antes de enviar a Meta

Instagram y Messenger tienen límites distintos según la documentación oficial de Meta (Mayo 2026).

### Límites oficiales de Meta

| Tipo | Instagram | Messenger |
|------|-----------|-----------|
| **Imagen (URL)** | 8 MB | 8 MB |
| **Imagen (directa)** | 25 MB | 25 MB |
| **Audio** | 25 MB | 25 MB |
| **Video** | 25 MB | 25 MB |
| **Video (URL)** | — | 75 seg timeout |
| **Archivo** | 25 MB | 25 MB |
| **Sticker** | WebP | WebP |
| **Múltiples imágenes** | Hasta 10 | Hasta 30 |

### MIME types aceptados por Meta

| Tipo | Formatos |
|------|----------|
| **image** | `image/jpeg`, `image/png`, `image/gif`, `image/webp` |
| **audio** | `audio/mpeg`, `audio/mp4`, `audio/aac`, `audio/ogg`, `audio/wav` |
| **video** | `video/mp4`, `video/ogg`, `video/avi`, `video/mov`, `video/webm` |
| **file** | `text/plain`, `application/pdf`, `application/msword`, `application/vnd.ms-excel`, `application/vnd.openxmlformats-officedocument.*`, `application/zip` |

## TODOs

- [ ] 1. Actualizar `config/instagram.php` — sección `media` completa

  **Qué hacer**: Reemplazar la sección `media` actual (solo 3 keys) por estructura completa:

  ```php
  'media' => [
      'disk' => env('INSTAGRAM_MEDIA_DISK', 'public'),
      'base_path' => env('INSTAGRAM_MEDIA_PATH', 'instagram'),
      
      'storage_path' => [
          'images' => storage_path('app/public/instagram/images'),
          'audios' => storage_path('app/public/instagram/audios'),
          'videos' => storage_path('app/public/instagram/videos'),
          'documents' => storage_path('app/public/instagram/documents'),
      ],
      
      'max_file_size' => [
          'image' => 8 * 1024 * 1024,    // 8MB (URL)
          'audio' => 25 * 1024 * 1024,   // 25MB
          'video' => 25 * 1024 * 1024,   // 25MB
          'file' => 25 * 1024 * 1024,    // 25MB
      ],
      
      'allowed_types' => [
          'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
          'audio' => ['audio/mpeg', 'audio/mp4', 'audio/aac', 'audio/ogg', 'audio/wav'],
          'video' => ['video/mp4', 'video/ogg', 'video/avi', 'video/mov', 'video/webm'],
          'file' => [
              'text/plain', 'application/pdf',
              'application/msword', 'application/vnd.ms-excel',
              'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
              'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
              'application/zip',
          ],
      ],
  ],
  ```

  **Commit**: `feat(instagram): gestión completa de archivos multimedia por tipo y MIME`

- [ ] 2. Actualizar `config/facebook.php` — sección `media` completa

  **Qué hacer**: Misma estructura, pero con límites de Messenger:
  
  ```php
  'max_file_size' => [
      'image' => 8 * 1024 * 1024,    // 8MB (URL)
      'audio' => 25 * 1024 * 1024,   // 25MB
      'video' => 25 * 1024 * 1024,   // 25MB
      'file' => 25 * 1024 * 1024,    // 25MB
  ],
  ```

  **Commit**: `feat(messenger): gestión completa de archivos multimedia por tipo y MIME`

- [ ] 3. Agregar validación en `ApiClient::sendMediaRequest()`

  **Qué hacer**: Antes de enviar, validar:
  1. **Tamaño**: verificar que el archivo no exceda `max_file_size[$type]`
  2. **MIME**: verificar que el MIME detectado esté en `allowed_types[$type]`
  3. **Lanzar excepción clara** si no cumple, con mensaje específico

  ```php
  // En sendMediaRequest(), antes del bloque multipart/JSON:
  if ($this->isLocalFile($media)) {
      $filePath = ...;
      $this->validateMediaFile($filePath, $mediaType, $platform);
  }
  
  protected function validateMediaFile(string $path, string $type, string $platform): void
  {
      $maxSize = config("{$platform}.media.max_file_size.{$type}");
      $allowedTypes = config("{$platform}.media.allowed_types.{$type}");
      $mime = $this->getMimeType($type, $path);
      
      if (filesize($path) > $maxSize) {
          throw new \RuntimeException("File size exceeds {$type} limit of " . ($maxSize/1024/1024) . "MB");
      }
      
      if (!in_array($mime, $allowedTypes)) {
          throw new \RuntimeException("MIME type '{$mime}' not allowed for {$type}. Allowed: " . implode(', ', $allowedTypes));
      }
  }
  ```

  **Commit**: `feat(api): validación de tamaño y MIME en sendMediaRequest`

- [ ] 4. Actualizar `MessengerMessageService::downloadMediaFile()` — usar rutas por tipo

  **Qué hacer**: En vez de construir la ruta con `config('facebook.media.path') . '/images/...'`, usar `config('facebook.media.storage_path.images')`.

  **Commit**: `refactor(messenger): usar storage_path por tipo de archivo`

- [ ] 5. Actualizar wizard — mostrar variables de media completas

  **Qué hacer**: Actualizar `.env` output para mostrar todas las variables de media (tamaños por tipo, MIME types como comentarios).

  **Commit**: `docs(wizard): variables de media completas en instalación`

## Verificación

```bash
# Estructura de media completa en ambos configs
grep -c "max_file_size" config/instagram.php config/facebook.php  # 2+ cada uno
grep -c "allowed_types" config/instagram.php config/facebook.php  # 2+ cada uno
grep -c "storage_path" config/instagram.php config/facebook.php   # 2+ cada uno

# Validación en ApiClient
grep -c "validateMediaFile" src/InstagramApi/ApiClient.php  # 1+
```
