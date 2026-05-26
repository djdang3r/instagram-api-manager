[◄◄ Mensajes](04-mensajes.md)
[▲ Tabla de contenido](00-tabla-de-contenido.md)
[Enlaces y QR ►►](06-enlaces.md)

# 🛠️ Menú Persistente e Ice Breakers

Configura la experiencia automatizada de tus usuarios en Instagram Direct.

### 1. Menú Persistente

El menú persistente es un menú que siempre está disponible en la interfaz de chat de Instagram.

```php
use ScriptDevelop\InstagramApiManager\Facades\Instagram;

// Crear botones
$buttons = [
    Instagram::persistentMenu()->createPostbackButton('Ver Productos', 'VIEW_PRODUCTS'),
    Instagram::persistentMenu()->createUrlButton('Visitar Sitio', 'https://tienda.com', 'full')
];

// Crear el menú localizado (default es obligatorio)
$menu = Instagram::persistentMenu()->createLocalizedMenu('default', false, $buttons);

// Establecer el menú
$result = Instagram::persistentMenu()
    ->withAccessToken($account->access_token)
    ->withInstagramUserId($account->instagram_business_account_id)
    ->setPersistentMenu([$menu]);
```

### 2. Ice Breakers (Rompehielos)

Los Ice Breakers son preguntas que aparecen a los usuarios que nunca han iniciado una conversación con tu cuenta.

```php
// Crear acciones
$actions = [
    Instagram::persistentMenu()->createIceBreakerAction('¿Cuáles son sus horarios?', 'HOURS_REQ'),
    Instagram::persistentMenu()->createIceBreakerAction('Soporte Técnico', 'SUPPORT_REQ')
];

// Crear ice breaker
$iceBreaker = Instagram::persistentMenu()->createIceBreaker('default', $actions);

// Establecer ice breakers
$result = Instagram::persistentMenu()
    ->withAccessToken($account->access_token)
    ->withInstagramUserId($account->instagram_business_account_id)
    ->setIceBreakers([$iceBreaker]);
```

### 3. Gestión (Get / Delete)

Puedes consultar o eliminar estas configuraciones en cualquier momento:

```php
// Obtener menú actual
$currentMenu = Instagram::persistentMenu()->getPersistentMenu();

// Eliminar Ice Breakers
$result = Instagram::persistentMenu()->deleteIceBreakers();
```

---
[◄◄ Mensajes](04-mensajes.md) | [Enlaces y QR ►►](06-enlaces.md)
