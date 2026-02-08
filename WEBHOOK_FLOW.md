# Flujo de Recepción y Almacenamiento de Mensajes de Instagram

## 🔄 Flujo Completo

```
INSTAGRAM → WEBHOOK HTTP POST → InstagramWebhookController
                                    ↓
                          handleEvent(Request)
                                    ↓
                    Valida cada entrada (entry)
                                    ↓
                  Para cada mensaje (messaging)
                                    ↓
        Llama: $messageService->processWebhookMessage($messaging)
                                    ↓
                    InstagramMessageService::processMessage()
                                    ↓
        1. Valida sender_id y recipient_id
        2. Busca cuenta de negocio en BD (instagram_business_accounts)
        3. Busca o crea conversación (instagram_conversations)
        4. Actualiza conversación (last_message_at, unread_count)
        5. Determina tipo de evento (mensaje, postback, reacción, etc.)
                                    ↓
            PARA MENSAJES: processIncomingMessage()
                                    ↓
            📝 Prepara datos del mensaje
            💾 INSERTA EN: instagram_messages
            ✅ Logea confirmación con ID guardado
```

## 📂 Archivos Involucrados

### 1. **Controlador** (punto de entrada del webhook)
- **Archivo**: `src/Http/Controllers/InstagramWebhookController.php`
- **Método**: `handle(Request $request)`
- **Función**: Recibe POST del webhook de Instagram y valida

### 2. **Servicio** (procesa y almacena)
- **Archivo**: `src/Services/InstagramMessageService.php`
- **Métodos Principales**:
  - `processWebhookMessage($messaging)` - Entrada principal
  - `processMessage($messageData)` - Lógica principal
  - `processIncomingMessage($conversation, $message, $senderId, $recipientId)` - **AQUÍ SE GUARDA EN BD**

### 3. **Modelos** (tablas de BD)
- `instagram_conversations` - Conversaciones
- `instagram_messages` - **DONDE SE GUARDAN LOS MENSAJES**
- `instagram_business_accounts` - Cuentas de negocio
- `instagram_contacts` - Contactos

## 💾 Datos Almacenados en `instagram_messages`

Cuando se recibe un mensaje, se almacenan estos campos:

```php
[
    'conversation_id' => ID de la conversación,
    'message_id' => ID único del mensaje (de Instagram),
    'message_method' => 'incoming',
    'message_type' => 'text' | 'image' | 'video' | 'postback' | 'quick_reply' | etc,
    'message_from' => ID del usuario que envía,
    'message_to' => ID del receptor,
    'message_content' => Texto del mensaje,
    'attachments' => JSON con adjuntos (imágenes, videos, etc),
    'json_content' => JSON completo del mensaje,
    'status' => 'received',
    'created_time' => Timestamp actual,
    'sent_at' => Timestamp del mensaje
]
```

## 📊 Ejemplo de Logs

Cuando se recibe un mensaje, verás en los logs:

```
═══════════════════════════════════════════════════════
🔄 INICIANDO PROCESAMIENTO DE MENSAJE
📨 MENSAJE RECIBIDO EN EL WEBHOOK
   sender_id: 12345678
   recipient_id: 87654321
   has_message: true
   message_type: text_message
🔎 BUSCANDO CUENTA DE NEGOCIO EN BD
✅ Cuenta de negocio encontrada
✅ Conversación lista
⏰ Actualizando datos de conversación...
✅ Conversación actualizada
📋 Determinando tipo de evento...
→ Es un MENSAJE TEXT/MEDIA
📝 PREPARANDO DATOS PARA GUARDAR EN BD
💾 GUARDANDO MENSAJE EN LA BASE DE DATOS (tabla: instagram_messages)
✅ MENSAJE GUARDADO EN BD
   id: abc123
   message_id: mid.xxxxx
   type: text
   from: 12345678
✨ RESUMEN FINAL DEL MENSAJE ALMACENADO
✅ PROCESAMIENTO COMPLETADO EXITOSAMENTE
═══════════════════════════════════════════════════════
```

## 🔍 Cómo Ver los Logs en Tiempo Real

```bash
# Terminal Windows (PowerShell)
Get-Content -Path "storage/logs/instagram.log" -Wait

# O buscar líneas importantes
Get-Content "storage/logs/instagram.log" | Select-String "💾|✅|❌"
```

## ⚠️ Errores Comunes

### Error: "LA CUENTA DE INSTAGRAM BUSINESS NO EXISTE EN BD"
**Causa**: La cuenta de Instagram no está conectada
**Solución**: Autenticarse con Instagram primero

### Error: "Datos inválidos: falta sender o recipient"
**Causa**: El webhook no tiene información de sender/recipient
**Solución**: Verificar configuración del webhook en Instagram

### Error: "Mensaje duplicado ignorado"
**Causa**: El mismo mensaje se recibió dos veces
**Solución**: Normal, el sistema evita duplicados automáticamente

## ✅ Checklist para Verificar que Funciona

- [ ] El webhook recibe POST (ve logs en InstagramWebhookController)
- [ ] Ve logs "🔄 INICIANDO PROCESAMIENTO DE MENSAJE"
- [ ] Ve logs "✅ Conversación lista"
- [ ] Ve logs "💾 GUARDANDO MENSAJE EN LA BASE DE DATOS"
- [ ] Ve logs "✅ MENSAJE GUARDADO EN BD"
- [ ] Compruebas en BD que la tabla `instagram_messages` tiene registros nuevos

## 🚀 Próximos Pasos

Si quieres agregar más funcionalidad:
- [ ] Notificaciones en tiempo real (WebSocket)
- [ ] Respuesta automática de mensajes
- [ ] Procesamiento de archivos media
- [ ] Historial de conversaciones
