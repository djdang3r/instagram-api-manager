[◄◄ Eventos](04-eventos.md)
[▲ Tabla de contenido](00-tabla-de-contenido.md)
[Notificaciones ►►](06-notificaciones.md)

# 👤 Perfil de Messenger — Configuración del Chat

Configurá la experiencia del chat de Messenger: mensaje de bienvenida, botón "Comenzar", menú persistente y preguntas sugeridas.

## 📋 Requisitos

- **Permiso**: `pages_messaging`
- **Token**: Page Access Token de la página
- **Rate limit**: 10 llamadas cada 10 minutos por página

## ⚙️ Configuración

```php
use ScriptDevelop\InstagramApiManager\Facades\Facebook;

$profile = Facebook::profile()
    ->withPageAccessToken('EAAxxx...');
```

## 💬 1. Mensaje de Bienvenida (Greeting)

El greeting aparece en la pantalla de inicio del chat, antes de que el usuario envíe su primer mensaje. Soporta múltiples idiomas.

```php
// Configurar greeting
$profile->setGreeting([
    ['locale' => 'default', 'text' => '¡Bienvenido! ¿En qué podemos ayudarte?'],
    ['locale' => 'en_US', 'text' => 'Welcome! How can we help you?'],
]);

// Obtener greeting actual
$greeting = $profile->getGreeting();

// Eliminar greeting
$profile->deleteGreeting();
```

## 🚀 2. Botón "Comenzar" (Get Started)

Aparece al iniciar un chat nuevo. Cuando el usuario lo presiona, se envía un postback a tu webhook.

```php
// Configurar botón
$profile->setGetStartedButton('GET_STARTED_PAYLOAD');

// Obtener payload actual
$payload = $profile->getGetStartedButton(); // "GET_STARTED_PAYLOAD"

// Eliminar botón
$profile->deleteGetStartedButton();
```

> 💡 Cuando el usuario presiona "Comenzar", recibís un evento `MessengerPostbackReceived` con el payload configurado.

## 📋 3. Menú Persistente

Menú de botones siempre visible en el chat. Soporta múltiples idiomas.

```php
$menu = [
    [
        'locale' => 'default',
        'call_to_actions' => [
            ['type' => 'postback', 'title' => '🛍️ Productos', 'payload' => 'MENU_PRODUCTS'],
            ['type' => 'postback', 'title' => '📦 Mis Pedidos', 'payload' => 'MENU_ORDERS'],
            ['type' => 'web_url', 'title' => '🌐 Tienda', 'url' => 'https://tu-tienda.com'],
        ],
    ],
];

// Configurar menú
$profile->setPersistentMenu($menu);

// Obtener menú actual
$currentMenu = $profile->getPersistentMenu();

// Eliminar menú
$profile->deletePersistentMenu();
```

## 🧊 4. Preguntas Sugeridas (Ice Breakers)

Preguntas que aparecen automáticamente al iniciar un chat nuevo. Hasta 4 preguntas.

```php
$questions = [
    ['question' => '¿Qué productos tienen?', 'payload' => 'ICE_PRODUCTS'],
    ['question' => '¿Horario de atención?', 'payload' => 'ICE_HOURS'],
    ['question' => '¿Hacen envíos?', 'payload' => 'ICE_SHIPPING'],
    ['question' => 'Hablar con un asesor', 'payload' => 'ICE_HUMAN'],
];

// Configurar ice breakers
$profile->setIceBreakers($questions);

// Obtener ice breakers actuales
$current = $profile->getIceBreakers();

// Eliminar ice breakers
$profile->deleteIceBreakers();
```

> 💡 Cada ice breaker tiene máximo 80 caracteres en la pregunta.

## 📊 Tabla de Métodos

| Método | Descripción | Retorno |
|--------|-------------|---------|
| `setGreeting(array)` | Configurar mensaje de bienvenida | `bool` |
| `getGreeting()` | Obtener greeting actual | `?array` |
| `deleteGreeting()` | Eliminar greeting | `bool` |
| `setGetStartedButton(string)` | Configurar botón "Comenzar" | `bool` |
| `getGetStartedButton()` | Obtener payload actual | `?string` |
| `deleteGetStartedButton()` | Eliminar botón | `bool` |
| `setPersistentMenu(array)` | Configurar menú persistente | `bool` |
| `getPersistentMenu()` | Obtener menú actual | `?array` |
| `deletePersistentMenu()` | Eliminar menú | `bool` |
| `setIceBreakers(array)` | Configurar ice breakers | `bool` |
| `getIceBreakers()` | Obtener ice breakers actuales | `?array` |
| `deleteIceBreakers()` | Eliminar ice breakers | `bool` |

## ❌ Errores Comunes

| Error | Causa | Solución |
|-------|-------|----------|
| Token inválido (190) | Token expirado | Refrescar token o reconectar página |
| Rate limit | Más de 10 llamadas en 10 min | Esperar o reducir frecuencia |
| `pages_messaging` denegado | Permiso no otorgado | Reconectar en `/facebook/connect` |

## 📋 Configuration Reference

| Config Key | Tipo | Default | Descripción |
|------------|------|---------|-------------|
| `facebook.models.messenger_conversation` | `string` (FQCN) | `MessengerConversation::class` | Modelo Eloquent de conversaciones |
| `facebook.api.version` | `string` | `v25.0` | Versión de Graph API |
| `facebook.api.timeout` | `int` | `30` | Timeout en segundos |
| `facebook.profile.greeting` | `array` | `[{locale: 'default', text: '...'}]` | Greeting default si no se setea |
| `facebook.broadcast.channel_type` | `string` | `public` | `public` o `private` para Reverb |
| `facebook.rate_limit.max_attempts` | `int` | `60` | Requests max por ventana |
| `facebook.rate_limit.decay_minutes` | `int` | `1` | Tamaño de la ventana de rate limit |
| `facebook.meta_auth.client_id` | `string` (env) | — | Facebook App ID |
| `facebook.meta_auth.client_secret` | `string` (env) | — | Facebook App Secret (encriptado en BD) |
| `facebook.meta_auth.redirect_uri` | `string` (env) | — | URL de callback OAuth |
| `facebook.meta_auth.custom_redirect_success_url` | `string` (env) | `null` | Redirect custom en éxito |
| `facebook.meta_auth.custom_redirect_error_url` | `string` (env) | `null` | Redirect custom en error |
| `facebook.meta_auth.custom_redirect_warning_url` | `string` (env) | `null` | Redirect custom en warning |

## ❓ FAQ

**¿Cuántas veces se muestra el greeting al usuario?**
Solo en la primera visita. Después no se vuelve a mostrar aunque el usuario cierre y abra el chat de nuevo.

**¿Los ice breakers son multilenguaje?**
Sí. Cada ice breaker puede tener `locale` específico. El sistema elige el que matchea el `locale` del cliente Messenger del usuario.

**¿Cuál es la diferencia entre `get_started` y `ice_breakers`?**
- `get_started`: un solo botón que se muestra UNA vez al primer contacto
- `ice_breakers`: hasta 4 preguntas sugeridas que SIEMPRE están visibles como chips arriba del input

**¿Persistent menu funciona en Instagram Messaging?**
Sí, desde Graph API v19.0+. Configurar igual que Messenger: `setPersistentMenu([...])` con `platform=instagram` opcional.

---

[◄◄ Eventos](04-eventos.md) | [Notificaciones ►►](06-notificaciones.md)

## 🧪 Testing desde Proyecto Laravel Externo

Este paquete NO incluye tests internos (se testean desde un proyecto Laravel externo). Aquí está el patrón recomendado usando Testbench + PHPUnit.


## 📚 Payload de get_started

```json
{
  "get_started": {
    "payload": "GET_STARTED_CLICKED"
  }
}
```

Cuando el usuario hace clic, Meta envía webhook con `postback.payload = "GET_STARTED_CLICKED"`. Tu listener puede usar este evento para triggerar un welcome message.

## 🔄 Locale Matching

Meta elige el `locale` del cliente Messenger del usuario y busca el primer match en tu array:

```php
$profile->setGreeting([
    ['locale' => 'default', 'text' => 'Hi!'],         // Fallback
    ['locale' => 'en_US', 'text' => 'Hello!'],
    ['locale' => 'es_ES', 'text' => '¡Hola!'],
    ['locale' => 'es_MX', 'text' => '¡Qué onda!'],
]);
```

Si el usuario tiene `es_MX` y vos solo tenés `es_ES`, Meta NO matchea — usa `default`.

## 🎨 Persistent Menu: Ejemplo Completo

```php
$profile->setPersistentMenu([
    [
        'locale' => 'default',
        'composer_input_disabled' => false,
        'call_to_actions' => [
            ['type' => 'postback', 'title' => 'Ver productos', 'payload' => 'VIEW_PRODUCTS'],
            ['type' => 'postback', 'title' => 'Hablar con humano', 'payload' => 'TALK_HUMAN'],
            [
                'type' => 'web_url',
                'title' => 'Visitar web',
                'url' => 'https://misitio.com',
                'webview_height_ratio' => 'full',
            ],
            [
                'type' => 'nested',
                'title' => 'Más opciones',
                'call_to_actions' => [
                    ['type' => 'postback', 'title' => 'Ayuda', 'payload' => 'HELP'],
                    ['type' => 'postback', 'title' => 'Contacto', 'payload' => 'CONTACT'],
                ],
            ],
        ],
    ],
]);
```

