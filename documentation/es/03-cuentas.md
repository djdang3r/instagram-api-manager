[◄◄ Configuración](02-configuracion.md)
[▲ Tabla de contenido](00-tabla-de-contenido.md)
[Gestión de Mensajes ►►](04-mensajes.md)

# 👤 Gestión de Cuentas y Perfiles

El paquete facilita la obtención de cuentas y la gestión de perfiles de Instagram Business.

### 1. Flujo de Autenticación (OAuth)

Para obtener la URL de autorización y permitir que un usuario vincule su cuenta de Instagram:

```php
use ScriptDevelop\InstagramApiManager\Facades\Instagram;

$url = Instagram::account()->getAuthorizationUrl();

return redirect($url);
```

### 2. Vincular una Cuenta Específica

Una vez tengas el token o la cuenta almacenada, puedes inicializar el servicio para interactuar con ella:

```php
use ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount;
use ScriptDevelop\InstagramApiManager\Facades\Instagram;

// Por modelo
$account = InstagramBusinessAccount::first();
$profile = Instagram::forAccount($account)->getProfileInfo();

// Por ID de cuenta (IGSID)
$media = Instagram::account('17918115224312316')->getUserMedia();
```

### 3. Sincronización de Datos

El paquete utiliza modelos Eloquent para persistir la información. Puedes usar el `ApiClient` interno si necesitas realizar consultas personalizadas o sincronizar manualmente las páginas gestionadas:

```php
$pages = Instagram::account()->getUserManagedPages($accessToken);
```

### 4. Modelos Disponibles

- `InstagramBusinessAccount`: Representa la cuenta de negocio de Meta linked a Instagram.
- `InstagramProfile`: Almacena detalles públicos del perfil del usuario.
- `InstagramContact`: Gestiona los usuarios de Instagram que interactúan con tu cuenta.

---
[◄◄ Configuración](02-configuracion.md) | [Gestión de Mensajes ►►](04-mensajes.md)
