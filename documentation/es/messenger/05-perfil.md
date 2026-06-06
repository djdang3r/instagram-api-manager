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

---

[◄◄ Eventos](04-eventos.md) | [Notificaciones ►►](06-notificaciones.md)
