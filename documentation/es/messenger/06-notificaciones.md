[◄◄ Perfil](05-perfil.md)
[▲ Tabla de contenido](00-tabla-de-contenido.md)
[Insights ►►](07-insights.md)

# 🔔 Notificaciones One-Time — Messenger

Enviá un mensaje fuera de la ventana de 24 horas solicitando permiso al usuario. Ideal para recordatorios, alertas de stock y seguimiento post-venta.

## 📋 Requisitos

- **Permiso**: `pages_messaging`
- El usuario debe haber interactuado previamente con la página
- El usuario debe aceptar explícitamente recibir la notificación

## 🚀 Flujo Completo

### Paso 1: Solicitar permiso

```php
use ScriptDevelop\InstagramApiManager\Facades\Facebook;

$token = Facebook::message()
    ->withPageAccessToken('EAAxxx...')
    ->withPageId('PAGE_ID')
    ->requestOneTimeNotification(
        recipientId: 'PSID',
        title: '¿Te avisamos cuando vuelva el stock?',
        payload: 'BACK_IN_STOCK_123'
    );

if ($token) {
    // Guardar el token para usarlo después
    // $token = "NOTIF_TOKEN_ABC123"
}
```

> 💡 El usuario ve un mensaje en Messenger preguntando si quiere recibir la notificación. Si acepta, Meta devuelve el token.

### Paso 2: Enviar la notificación (cuando corresponda)

```php
Facebook::message()
    ->withPageAccessToken('EAAxxx...')
    ->withPageId('PAGE_ID')
    ->sendOneTimeNotification(
        recipientId: 'PSID',
        token: 'NOTIF_TOKEN_ABC123',
        message: '¡Buenas noticias! El producto #123 ya está disponible 🎉'
    );
```

> ⚠️ Solo podés enviar **1 mensaje** por token. Después de usado, el token expira.

## 📊 Tabla de Métodos

| Método | Descripción | Retorno |
|--------|-------------|---------|
| `requestOneTimeNotification(string, string, string)` | Solicitar permiso al usuario | `?string` (token) |
| `sendOneTimeNotification(string, string, string)` | Enviar notificación con token | `?array` |

## ❌ Errores Comunes

| Error | Causa | Solución |
|-------|-------|----------|
| Token inválido | Token ya usado o expirado | Solicitar nuevo permiso |
| Usuario no aceptó | El usuario rechazó la solicitud | No se puede forzar |
| Fuera de ventana sin token | No se puede enviar sin one-time token | Usar `requestOneTimeNotification` primero |

---

[◄◄ Perfil](05-perfil.md) | [Insights ►►](07-insights.md)
