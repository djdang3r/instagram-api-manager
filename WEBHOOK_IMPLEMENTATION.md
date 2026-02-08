# 📱 Sistema de Recepción y Almacenamiento de Mensajes de Instagram

## ¿Qué se ha implementado?

✅ **Recepción completa de eventos del webhook de Instagram**
✅ **Almacenamiento automático de mensajes en BD**
✅ **Logging detallado en cada paso del proceso**
✅ **Manejo de múltiples tipos de eventos**
✅ **Validación de autenticidad del webhook**
✅ **Tests automatizados**
✅ **Comando de testing manual**

---

## 🚀 Cómo Funciona

### Flujo Simple:

```
Instagram envía mensaje → Webhook recibe → Se procesa → Se almacena en BD
```

### Paso a Paso:

1. **Usuario envía mensaje en Instagram**
2. **Instagram envía POST a tu webhook** (`/instagram/webhook`)
3. **InstagramWebhookController recibe el POST**
4. **InstagramMessageService procesa el evento**
5. **El mensaje se almacena en tabla `instagram_messages`**
6. **Se logea cada paso para debugging**

---

## 📊 Tabla de Almacenamiento: `instagram_messages`

Cuando se recibe un mensaje, se guarda con esta información:

| Campo | Descripción |
|-------|-------------|
| `id` | ID único en BD (ULID) |
| `conversation_id` | A qué conversación pertenece |
| `message_id` | ID del mensaje en Instagram |
| `message_type` | Tipo: text, image, video, postback, quick_reply, etc |
| `message_from` | Quién envió el mensaje (ID de Instagram) |
| `message_to` | Quién recibe el mensaje (ID de tu negocio) |
| `message_content` | Texto del mensaje |
| `attachments` | JSON con imágenes, videos, etc |
| `status` | Estado: received, read, etc |
| `sent_at` | Cuándo se envió el mensaje |

---

## 📝 Cómo Ver los Logs

### Opción 1: Ver TODOS los logs

```powershell
# En terminal PowerShell
Get-Content -Path "storage/logs/instagram.log" -Wait
```

### Opción 2: Ver solo logs importantes

```powershell
# Ver con emojis
Get-Content "storage/logs/instagram.log" | Select-String "📨|💾|✅|❌"
```

### Opción 3: Últimas líneas

```powershell
# Últimas 50 líneas
Get-Content "storage/logs/instagram.log" | Select-Object -Last 50
```

---

## 🧪 Testing del Webhook

### Opción A: Comando Artisan (Recomendado)

```bash
# Test con mensaje de texto
php artisan instagram:test-webhook --type=message

# Test con postback (botón)
php artisan instagram:test-webhook --type=postback

# Test con imagen
php artisan instagram:test-webhook --type=image

# Test con reacción
php artisan instagram:test-webhook --type=reaction
```

### Opción B: Tests Automatizados

```bash
# Ejecutar todos los tests del webhook
php artisan test --filter="InstagramWebhookMessagesTest"

# O específicamente uno
php artisan test --filter="test_recibir_mensaje_de_texto"
```

### Opción C: Con cURL (manual)

```bash
# Test simple de mensaje
curl -X POST http://localhost:8000/instagram/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "object": "instagram",
    "entry": [{
      "id": "123456",
      "messaging": [{
        "sender": {"id": "USER_ID"},
        "recipient": {"id": "PAGE_ID"},
        "message": {
          "mid": "mid.123",
          "text": "Hola desde curl!"
        }
      }]
    }]
  }'
```

---

## 📋 Tipos de Eventos que Maneja

| Evento | Descripción | Almacena |
|--------|-------------|----------|
| **message** | Texto, imágenes, videos | ✅ Sí |
| **postback** | Click en botones | ✅ Sí |
| **quick_reply** | Respuestas rápidas | ✅ Sí |
| **reaction** | Emojis de reacción | ✅ Sí |
| **read** | Confirmación de lectura | ⚠️ Parcial |
| **message_edit** | Edición de mensaje | ✅ Sí |
| **referral** | Referencias/compartir | ✅ Sí |
| **optin** | Opt-in/permisos | ✅ Sí |

---

## 🔍 Archivos Clave

### Controlador (entrada del webhook)
**Archivo**: `src/Http/Controllers/InstagramWebhookController.php`

```php
// Aquí llega POST de Instagram
public function handle(Request $request)
{
    if ($request->isMethod('get')) {
        return $this->handleVerification($request);  // Verificar webhook
    }
    if ($request->isMethod('post')) {
        return $this->handleEvent($request);  // ← Aquí se procesan eventos
    }
}
```

### Servicio (procesamiento)
**Archivo**: `src/Services/InstagramMessageService.php`

```php
// Aquí se procesa y almacena en BD
public function processWebhookMessage(array $messaging)
{
    // 1. Valida datos
    // 2. Busca cuenta de negocio
    // 3. Busca/crea conversación
    // 4. Procesa el evento
    // 5. Almacena en instagram_messages ← AQUÍ
}
```

---

## ✅ Verificar que Todo Funciona

### Checklist:

1. **¿Recibe el webhook?**
   - Envía POST con comando: `php artisan instagram:test-webhook`
   - Verifica que no da error 404

2. **¿Procesa correctamente?**
   - Mira logs: `Get-Content "storage/logs/instagram.log" -Wait`
   - Deberías ver: `🔄 INICIANDO PROCESAMIENTO DE MENSAJE`

3. **¿Almacena en BD?**
   - Abre tu gestor de BD
   - Tabla: `instagram_messages`
   - Deberías ver registros nuevos

4. **¿Todo OK?**
   - Verás en logs: `✅ PROCESAMIENTO COMPLETADO EXITOSAMENTE`

---

## ❌ Troubleshooting

### Problema: "LA CUENTA DE INSTAGRAM BUSINESS NO EXISTE EN BD"

**Causa**: No has autenticado la cuenta en Instagram

**Solución**:
1. Ve a la aplicación web
2. Haz click en "Conectar Instagram"
3. Autentica con Instagram
4. La cuenta se guardará en BD

### Problema: No ve logs

**Causa**: El log puede estar en otro archivo

**Solución**:
```bash
# Ver todos los archivos de log
Get-ChildItem "storage/logs/"

# Ver logs Laravel (no Instagram)
Get-Content "storage/logs/laravel.log" -Wait
```

### Problema: Webhook recibe pero no almacena

**Causa**: Error al procesar el evento

**Solución**:
1. Revisa logs detalladamente
2. Busca línea con `❌` en rojo
3. Ahí está el error específico

---

## 🎯 Casos de Uso

### 1. Sistema de Atención al Cliente
```
Cliente envía mensaje → Se almacena en BD → 
Tu equipo responde desde panel → Respuesta se envía a Instagram
```

### 2. Chatbot Automático
```
Cliente envía mensaje → Se procesa automáticamente → 
Respuesta automática se envía
```

### 3. Análisis de Conversaciones
```
Todos los mensajes guardados → Análisis de sentimiento →
Reportes y estadísticas
```

### 4. Integración con CRM
```
Mensaje recibido → Se sincroniza con CRM → 
Se actualiza historial del cliente
```

---

## 📚 Documentación Adicional

- **Flujo Completo**: Lee `WEBHOOK_FLOW.md`
- **Estructura de BD**: Revisa las migraciones en `database/migrations/`
- **Modelos**: Mira `src/Models/InstagramMessage.php`

---

## 🚀 Próximos Pasos Sugeridos

1. **Implementar respuesta automática** de mensajes
2. **Agregar notificaciones** cuando llega mensaje
3. **Crear dashboard** para ver mensajes
4. **Guardar estadísticas** de conversaciones
5. **Implementar buscar** en mensajes históricos

---

## 💡 Tips

- ✅ Los logs tienen emojis para buscar rápido
- ✅ Cada mensaje tiene un ID único en BD
- ✅ Las conversaciones se crean automáticamente
- ✅ Los duplicados se descartan automáticamente
- ✅ Puedes ver datos JSON completo en `json_content`

---

**¿Dudas?** Revisa los logs, ahí está el 90% de la información que necesitas.
