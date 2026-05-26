# 🔗 Autenticación y Conexión de Páginas — Facebook Messenger

## 📋 Requisitos Previos

Antes de usar la mensajería de Messenger, necesitás:

1. Una **App de Meta** registrada en [Meta for Developers](https://developers.facebook.com/)
2. Una **página de Facebook** donde tengas rol de administrador
3. Los siguientes **permisos** configurados en tu App de Meta:
   - `pages_show_list` — Ver tus páginas
   - `pages_read_engagement` — Leer conversaciones
   - `pages_messaging` — Enviar y recibir mensajes

## ⚙️ Variables de Entorno

Agregá estas variables a tu archivo `.env`:

```env
# Facebook OAuth
FACEBOOK_CLIENT_ID=123456789012345
FACEBOOK_CLIENT_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
FACEBOOK_REDIRECT_URI=https://tu-dominio.com/facebook/callback

# Facebook API
FACEBOOK_API_BASE_URL=https://graph.facebook.com
FACEBOOK_API_VERSION=v25.0
FACEBOOK_API_TIMEOUT=30

# Facebook Webhook
FACEBOOK_WEBHOOK_VERIFY_TOKEN=tu_token_secreto

# Facebook Broadcast (Laravel Reverb)
FACEBOOK_BROADCAST_CHANNEL_TYPE=public

# Facebook Media
FACEBOOK_MEDIA_DISK=public
FACEBOOK_MEDIA_PATH=facebook
```

## 🚀 Flujo de Conexión OAuth

### Paso 1: Iniciar la conexión

El paquete expone la ruta `/facebook/connect`. Al visitarla, redirige al usuario a Facebook para autorizar la app:

```php
// Desde una vista Blade
<a href="{{ route('facebook.connect') }}" class="btn btn-primary">
    Conectar Página de Facebook
</a>

// O programáticamente
use ScriptDevelop\InstagramApiManager\Facades\Facebook;
return redirect()->route('facebook.connect');
```

### Paso 2: El usuario autoriza

Facebook muestra un diálogo donde el usuario:
1. Inicia sesión (si no lo está)
2. **Selecciona qué páginas** quiere compartir con la app
3. Acepta los permisos solicitados

> 💡 El usuario puede elegir compartir una, varias, o ninguna página. Solo las páginas seleccionadas serán accesibles.

### Paso 3: Callback automático

Facebook redirige a `/facebook/callback?code=XXX`. El paquete automáticamente:

1. Intercambia el `code` por un **User Access Token**
2. Consulta `GET /me/accounts` para obtener todas las páginas autorizadas
3. Por cada página, guarda en la base de datos:
   - `page_id` — ID único de la página
   - `name` — Nombre de la página
   - `access_token` — **Page Access Token** (encriptado)
   - `tasks` — Permisos del usuario sobre la página
   - `instagram_business_account` — ID de Instagram vinculado (si existe)

```php
// FacebookAccountService::handleCallback() hace todo esto automáticamente
// Resultado: una fila en facebook_pages por cada página autorizada
```

> ⚠️ **Importante**: Cada página tiene su propio `access_token`. Ese token es el que se usa para enviar/recibir mensajes, NO el token del usuario.

### Paso 4: Verificar la conexión

```php
use ScriptDevelop\InstagramApiManager\Models\FacebookPage;

// Listar todas las páginas conectadas
$pages = FacebookPage::all();
foreach ($pages as $page) {
    echo $page->name . ' — ' . $page->page_id;
}
```

## 📝 Registro Manual (sin OAuth)

Si preferís no usar el flujo OAuth, podés registrar una página manualmente:

```php
use ScriptDevelop\InstagramApiManager\Models\FacebookPage;

FacebookPage::create([
    'page_id' => '10234567890123',        // ID de tu página
    'name' => 'Mi Negocio',
    'access_token' => 'EAAxxx...',        // Page Access Token (se encripta automáticamente)
    'tasks' => ['MESSAGING', 'MANAGE'],
]);
```

> ⚠️ El Page Access Token lo obtenés desde el dashboard de Meta: Configuración de la página → Configuración avanzada de mensajería → Token de acceso.

## 🔑 Diferencia con Instagram

| | Instagram | Facebook Messenger |
|---|---|---|
| **Qué se autentica** | Cuenta de Instagram Business | Usuario de Facebook → sus Páginas |
| **Cuántos tokens** | 1 por cuenta | 1 por cada página |
| **Token para mensajería** | Access token de la cuenta IG | Page Access Token |
| **Ruta de conexión** | `/instagram/connect` | `/facebook/connect` |
| **Callback** | `/instagram/callback` | `/facebook/callback` |

## ❌ Errores Comunes

| Error | Causa | Solución |
|-------|-------|----------|
| `redirect_uri_mismatch` | La URI de redirección no coincide con la configurada en Meta | Verificar `FACEBOOK_REDIRECT_URI` en `.env` y en el dashboard de Meta |
| Sin páginas en callback | El usuario no seleccionó ninguna página | El usuario debe marcar al menos una página en el diálogo de Facebook |
| Token inválido (190) | El Page Access Token expiró o fue revocado | Re-conectar la página desde `/facebook/connect` |
