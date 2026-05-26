# Mini-Plan: Soporte de archivos locales en envío de media

## TL;DR
> **Resumen**: Modificar `ApiClient` agregando helper `sendMediaRequest()` que detecta si el media es URL o archivo local. Si es archivo, usa multipart/form-data con `filedata`. Actualizar 10 métodos en Instagram y Messenger.
> **Esfuerzo**: Corto
> **Archivos**: 3 (ApiClient, InstagramMessageService, FacebookMessageService)
> **No toca**: Base de datos, rutas, configs, eventos

## Contexto
La API de Meta soporta envío de archivos locales mediante `multipart/form-data` con el campo `filedata`:
```bash
curl -F 'recipient={"id":"<ID>"}' \
     -F 'message={"attachment":{"type":"image", "payload":{"is_reusable":true}}}' \
     -F 'filedata=@/ruta/imagen.png;type=image/png' \
     "https://graph.facebook.com/v25.0/PAGE_ID/messages?access_token=TOKEN"
```

## TODOs

- [ ] 1. Agregar `sendMediaRequest()` en ApiClient

  **Qué hacer**: Agregar 3 métodos a `src/InstagramApi/ApiClient.php`:

  1. `sendMediaRequest(string $endpoint, array $recipient, string $mediaType, string|array $media, array $extraPayload, array $query): mixed`
     - Si `$media` es array (múltiples imágenes) → JSON normal
     - Si `$media` es archivo local → multipart con `filedata`
     - Si `$media` es URL → JSON normal
  2. `isLocalFile($media): bool` — detecta SplFileInfo o string de ruta existente
  3. `getMimeType(string $type, string $path): string` — detecta MIME con finfo

  **Perfil de Agente**: `quick` | **Commit**: `feat(api): agregar sendMediaRequest con soporte multipart para archivos locales`

- [ ] 2. Actualizar InstagramMessageService — 5 métodos

  **Qué hacer**: Modificar `src/Services/InstagramMessageService.php`:
  - `sendImageMessage($recipientId, string|\SplFileInfo $media, ...)` → usa `$this->apiClient->sendMediaRequest(...)`
  - `sendAudioMessage` → ídem
  - `sendVideoMessage` → ídem  
  - `sendDocumentMessage` (file) → ídem
  - `sendMultipleImages` → ídem (ya maneja array)

  **Commit**: `feat(instagram): soporte de archivos locales en envío de media`

- [ ] 3. Actualizar FacebookMessageService — 5 métodos

  **Qué hacer**: Mismos cambios en `src/Services/FacebookMessageService.php`:
  - `sendImageMessage`, `sendAudioMessage`, `sendVideoMessage`, `sendFileMessage`, `sendMultipleImages`
  - Agregar `messaging_type` al `extraPayload`

  **Commit**: `feat(messenger): soporte de archivos locales en envío de media`

- [ ] 4. Actualizar documentación — ambos servicios

  **Qué hacer**:
  - `documentation/es/instagram/04-mensajes.md` — actualizar ejemplos de `sendImageMessage`, `sendAudioMessage`, etc. para mostrar ambas formas (URL y archivo local)
  - `documentation/es/messenger/02-mensajes.md` — ídem
  - Agregar nota sobre `SplFileInfo` y rutas locales en cada método

  **Commit**: `docs: actualizar ejemplos de envío de media con archivos locales`

- [ ] 5. Actualizar CHANGELOG

  **Qué hacer**: Agregar entrada en `[Unreleased]` → `### Changed`:
  ```
  - **Soporte de archivos locales**: `sendImageMessage`, `sendAudioMessage`, `sendVideoMessage`, `sendFileMessage` y `sendMultipleImages` ahora aceptan tanto URL como archivos locales (`SplFileInfo` o ruta string). El paquete detecta automáticamente y usa multipart/form-data con `filedata` para archivos.
  ```

  **Commit**: `chore: actualizar CHANGELOG con soporte de archivos locales`

## Lo que NO se toca
- Base de datos (sin cambios — mismos campos)
- `sendTextMessage`, `sendStickerMessage`, `sendQuickReplies`, templates, `sendReadReceipt`, `sendReaction`, `sendReply`
- Mensajes con URL — siguen funcionando idéntico
- Firma de `ApiClient::request()` — sin cambios

## Verificación
```bash
# Sin errores en archivos modificados
grep -c "sendMediaRequest\|isLocalFile\|getMimeType" src/InstagramApi/ApiClient.php
# Esperado: 3 métodos

# Los métodos de envío aceptan mixed media
grep "string|\\\\SplFileInfo|array.*\$media" src/Services/InstagramMessageService.php
# Esperado: coincidencias en sendImageMessage, sendAudioMessage, etc.
```
