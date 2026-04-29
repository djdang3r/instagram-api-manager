[◄◄ Enlaces y QR](06-enlaces.md)
[▲ Tabla de contenido](00-tabla-de-contenido.md)

# 📡 Webhooks y Eventos

El paquete maneja automáticamente la verificación y recepción de eventos de Instagram.

### 1. Ruta del Webhook

Al publicar las rutas, se registrará automáticamente:
- `POST /instagram-webhook`: Para recibir notificaciones.
- `GET /instagram-webhook`: Para la verificación de Meta.

### 2. Procesamiento de Mensajes

El `InstagramWebhookController` incluido se encarga de recibir el payload. Si deseas delegar la lógica, el paquete dispara procesos internos que puedes capturar.

Asegúrate de que tus modelos estén correctamente configurados en `config/instagram.php` para que el sistema sepa dónde guardar los mensajes entrantes.

### 3. Logging de Depuración

A partir de la versión `1.0.60`, el sistema incluye logs detallados en `storage/logs/instagram.log` que te permiten monitorizar el flujo de entrada:

- ✅ Identificación del remitente.
- ✅ Almacenamiento del mensaje en la base de datos.
- ✅ Gestión de estados (leído, entregado).

### 4. Personalización

Si necesitas una lógica muy específica, puedes sobreescribir el controlador o simplemente extender el servicio `InstagramMessageService` para añadir tus propios hooks de procesamiento.

```php
// En tu propio ServiceProvider o Controller
use ScriptDevelop\InstagramApiManager\Services\InstagramMessageService;

$service = app(InstagramMessageService::class);
$service->processWebhookMessage($payload);
```

---
[◄◄ Enlaces y QR](06-enlaces.md) | [▲ Tabla de contenido](00-tabla-de-contenido.md)
