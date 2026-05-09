[◄◄ Enlaces y QR](06-enlaces.md)
[▲ Tabla de contenido](00-tabla-de-contenido.md)
[Eventos en Tiempo Real ►►](08-eventos-tiempo-real.md)

# 📡 Webhooks y Eventos

El paquete maneja automáticamente la verificación y recepción de eventos de Instagram.

---

### 1. Ruta del Webhook

Al publicar las rutas, se registrará automáticamente:
- `POST /instagram-webhook`: Para recibir notificaciones.
- `GET /instagram-webhook`: Para la verificación de Meta.

---

### 2. Procesamiento de Mensajes

El `InstagramWebhookController` incluido actúa como un **delegador fino** que enruta las peticiones al procesador configurado. El procesador por defecto (`BaseWebhookProcessor`) se encarga de:

1. **Verificar** el webhook (GET) usando el `verify_token`
2. **Procesar** el payload (POST) iterando entries → messaging
3. **Almacenar** los mensajes en la base de datos a través de `InstagramMessageService`
4. **Disparar eventos broadcast** para cada tipo de mensaje (ver sección 3)

Asegúrate de que tus modelos estén correctamente configurados en `config/instagram.php` para que el sistema sepa dónde guardar los mensajes entrantes.

---

### 3. Eventos en Tiempo Real (Laravel Reverb) 🆕

> Esta funcionalidad está disponible a partir de la versión `1.0.81`.

Cada vez que se procesa un mensaje del webhook, el paquete **dispara automáticamente eventos broadcast** que puedes escuchar desde tu frontend en tiempo real con Laravel Echo + Reverb, o desde tu backend con listeners de Laravel.

**Consulta la guía completa en:** [📡 Eventos en Tiempo Real](08-eventos-tiempo-real.md)

---

### 4. Procesador de Webhook Personalizable 🆕

> Esta funcionalidad está disponible a partir de la versión `1.0.81`.

El paquete ahora utiliza el **patrón Strategy** para el procesamiento de webhooks. Puedes reemplazar completamente la lógica de procesamiento sin modificar el código del paquete.

#### ¿Cómo funciona?

1. El paquete define la interfaz `WebhookProcessorInterface` con tres métodos: `handle()`, `verifyWebhook()`, y `processWebhookPayload()`
2. La implementación por defecto es `BaseWebhookProcessor`
3. Puedes crear tu propia clase implementando la interfaz
4. Registras tu procesador en `.env` o `config/instagram.php`

#### Implementación por Defecto

```php
use ScriptDevelop\InstagramApiManager\Contracts\WebhookProcessorInterface;
use ScriptDevelop\InstagramApiManager\Services\WebhookProcessors\BaseWebhookProcessor;

// El paquete automáticamente usa BaseWebhookProcessor
// que hace todo el trabajo estándar + eventos broadcast
```

#### Crear tu Propio Procesador

```php
// App\Processors\MiProcesador.php
namespace App\Processors;

use ScriptDevelop\InstagramApiManager\Contracts\WebhookProcessorInterface;
use ScriptDevelop\InstagramApiManager\Services\InstagramMessageService;
use Illuminate\Http\Request;

class MiProcesador implements WebhookProcessorInterface
{
    public function __construct(
        protected InstagramMessageService $messageService
    ) {}

    public function handle(Request $request)
    {
        // Tu lógica personalizada aquí
    }

    public function verifyWebhook(Request $request)
    {
        // Tu verificación personalizada
    }

    public function processWebhookPayload(Request $request)
    {
        // Tu procesamiento personalizado
    }
}
```

#### Registrar tu Procesador

```env
# .env
INSTAGRAM_WEBHOOK_PROCESSOR=\App\Processors\MiProcesador
```

```php
// config/instagram.php
'webhook' => [
    'processor' => \App\Processors\MiProcesador::class,
],
```

---

### 5. Logging de Depuración

A partir de la versión `1.0.60`, el sistema incluye logs detallados en `storage/logs/instagram.log` que te permiten monitorizar el flujo de entrada:

- ✅ Identificación del remitente.
- ✅ Almacenamiento del mensaje en la base de datos.
- ✅ Gestión de estados (leído, entregado).
- ✅ Despacho de eventos broadcast (éxito/fallo).

---

### 6. Personalización Adicional

Si necesitas una lógica muy específica sin implementar el contrato completo, puedes extender el servicio `InstagramMessageService`:

```php
use ScriptDevelop\InstagramApiManager\Services\InstagramMessageService;

$service = app(InstagramMessageService::class);
$service->processWebhookMessage($payload);
```

O personalizar las clases de eventos individuales en `config/instagram.php`:

```php
'events' => [
    'message' => \App\Events\MiEventoMensaje::class,
    'postback' => \App\Events\MiEventoPostback::class,
    // ...
],
```

---

[◄◄ Enlaces y QR](06-enlaces.md) | [Eventos en Tiempo Real ►►](08-eventos-tiempo-real.md)
