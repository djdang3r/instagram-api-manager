# 9. Enlaces (m.me, ig.me) y Códigos QR

## Quick Start

```php
use ScriptDevelop\InstagramApiManager\Facades\Instagram;

// Generar enlace m.me para Messenger
$mmeLink = app('messenger.message')->generateMmeLink('1234567890');
// Output: https://m.me/YourPageName

// Generar enlace ig.me para Instagram
$igMeLink = app('instagram.link')->generateIgMeLink($instagramBusinessAccount, 'my_campaign');
// Output: https://ig.me/m/my_campaign?r=...

// NOTA: Generación de QR está deprecada (Google Charts API ya no funciona).
// Ver "Códigos QR" más abajo para implementar tu propio provider.
```

## ¿Para qué sirve?

Esta guía cubre dos tipos de enlaces profundos que Meta provee para que los usuarios inicien conversaciones con tus páginas/cuentas de negocio:

1. **Enlaces m.me** (Facebook Messenger): `https://m.me/<page_username>?ref=<reference>` — al hacer click abre Messenger con tu página
2. **Enlaces ig.me** (Instagram): `https://ig.me/m/<reference>` — abre Instagram con un mensaje pre-llenado

Estos enlaces son útiles para:
- **Campañas de marketing**: tracking de conversiones por campaña con el parámetro `ref`
- **CTAs en emails/newsletters**: links directos sin requerir que el usuario busque tu página
- **QR codes en material impreso**: volantes, tarjetas, packaging
- **Integración con ads**: parámetro `ref` se envía como webhook `messaging_referral` cuando el usuario llega

## Permisos requeridos

| Plataforma | Scope | Descripción |
|-----------|-------|-------------|
| Facebook | `pages_messaging` | Necesario para generar m.me links de páginas |
| Instagram | `instagram_basic` | Necesario para generar ig.me links de cuentas de negocio |

## Endpoints internos

### `MessengerLinkService::generateMmeLink(string $pageId, ?string $ref = null): string`

Genera un enlace m.me para una página de Facebook.

**Parámetros:**
- `$pageId` (string, requerido): El `page_id` de la página de Facebook (no el `name`)
- `$ref` (string|null, opcional): Parámetro de referencia para tracking. Max 2083 caracteres (límite URL).

**Retorna:** String con la URL completa (ej: `https://m.me/YourPageName?ref=my_campaign`)

**Throws:** `Exception` si la página no existe en la BD local.

**Ejemplo:**
```php
try {
    $url = app('messenger.message')->generateMmeLink('1234567890', 'summer_sale_2026');
    // Output: https://m.me/MyPage?ref=summer_sale_2026
} catch (Exception $e) {
    Log::error('Cannot generate m.me link', ['page_id' => '1234567890', 'error' => $e->getMessage()]);
}
```

### `InstagramLinkService::generateIgMeLink(Model $account, ?string $ref = null): string`

Genera un enlace ig.me para una cuenta de negocio de Instagram.

**Parámetros:**
- `$account` (Model, requerido): Instancia del modelo `InstagramBusinessAccount` (o el modelo custom configurado via `InstagramModelResolver`)
- `$ref` (string|null, opcional): Parámetro de referencia. Max 2083 caracteres.

**Retorna:** String con la URL completa.

**Ejemplo:**
```php
$account = InstagramModelResolver::instagram_business_account()->find($igUserId);
$url = app('instagram.link')->generateIgMeLink($account, 'product_launch');
```

## Códigos QR

> ⚠️ **DEPRECATED**: Los métodos `generateMmeQrCode()` y `generateIgMeQrCode()` retornan `null` desde la versión 1.1.0.

**¿Por qué?** Estos métodos dependían de la API de Google Charts (`https://chart.googleapis.com/chart?cht=qr&...`), que fue deprecada en 2012 y actualmente retorna errores. Implementar tu propio provider de QR es ahora responsabilidad del consumidor del paquete.

### Implementar tu propio provider

Recomendamos usar una de estas librerías PHP:
- **bacon/bacon-qr-code** (`composer require bacon/bacon-qr-code`) — liviana, sin dependencias GUI
- **endroid/qr-code** (`composer require endroid/qr-code`) — más features, soporta SVG/PNG/PDF
- **chillerlan/php-qrcode** (`composer require chillerlan/php-qrcode`) — completa, soporta múltiples encodings

**Ejemplo con chillerlan/php-qrcode:**
```php
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

$mmeLink = app('messenger.message')->generateMmeLink('1234567890');
$options = new QROptions(['outputType' => QRCode::OUTPUT_IMAGE_PNG, 'eccLevel' => QRCode::ECC_H]);
$qrCode = (new QRCode($options))->render($mmeLink);

// Guardar o retornar la imagen QR
return response($qrCode, 200, ['Content-Type' => 'image/png']);
```

## Eventos webhook

Cuando un usuario llega a Messenger vía un enlace m.me con parámetro `ref`, Meta envía un webhook `messaging_referral` con el payload:

```json
{
  "sender": {"id": "USER_PSID"},
  "recipient": {"id": "PAGE_ID"},
  "timestamp": 1234567890,
  "referral": {
    "ref": "summer_sale_2026",
    "source": "SHORTLINK",
    "type": "OPEN_THREAD"
  }
}
```

Este webhook es procesado por `MessengerWebhookProcessor` y se persiste en `messenger_referrals` table.

**Listener recomendado:**
```php
// En tu app/Listeners/ProcessReferralListener.php
public function handle(MessengerReferralReceived $event): void
{
    $ref = $event->referral['ref'] ?? null;
    if ($ref === 'summer_sale_2026') {
        // Track conversion, send welcome message, etc.
    }
}
```

## Manejo de errores

| Error | Causa | Solución |
|-------|-------|----------|
| `Exception: Facebook Page not found` | El `pageId` no existe en `facebook_pages` table | Verificar que la página esté sincronizada vía `SyncFacebookPages` o el wizard |
| `Exception: Instagram account not found` | El `$account` no es instancia válida | Verificar que la cuenta esté persistida en `instagram_business_accounts` table |
| QR retorna `null` | Deprecated por deprecation de Google Charts | Implementar provider custom (ver arriba) |

## Configuration Reference

| Config Key | Env Var | Default | Descripción |
|------------|---------|---------|-------------|
| `facebook.links.base_url` | `FACEBOOK_MME_BASE_URL` | `https://m.me` | URL base para m.me links (raramente se cambia) |

## FAQ

**¿Por qué m.me y no facebook.com/messages/?** El dominio `m.me` es el shortlink oficial de Meta que redirige automáticamente a Messenger (web o app nativa según el dispositivo).

**¿Cuántos caracteres puede tener el parámetro `ref`?** Limitado a 2083 (límite de URL en browsers antiguos). El paquete trunca automáticamente.

**¿Funciona el `ref` con deep links a Messenger app?** Sí, Meta preserva el parámetro `ref` cuando abre la app nativa.

**¿Puedo usar un dominio custom?** Sí, pero necesitas configurar `m.me` redirect en tu DNS. El paquete no incluye esa config.

## 🧪 Testing desde Proyecto Laravel Externo

Este paquete NO incluye tests internos (se testean desde un proyecto Laravel externo). Aquí está el patrón recomendado usando Testbench + PHPUnit.


## 🔄 Parseo del parámetro `ref`

El parámetro `ref` se envía como `messaging_referral.ref` en el webhook. Útil para tracking:

```php
public function handle(MessengerReferralReceived $event): void
{
    $ref = $event->referral['ref'] ?? null;
    
    // Ref puede tener formato "campaign_source_medium" (utm-like)
    // o un identificador custom
    if (str_starts_with($ref, 'utm_')) {
        [$_, $source, $medium] = explode('_', $ref);
        Log::info('Referral tracked', compact('source', 'medium'));
    }
    
    // Responder según el ref
    match ($ref) {
        'summer_sale_2026' => $this->sendSummerPromo($event),
        'product_launch' => $this->sendProductInfo($event),
        default => $this->sendDefaultWelcome($event),
    };
}
```

## 🆚 m.me vs ig.me: diferencias técnicas

| Aspecto | m.me (Messenger) | ig.me (Instagram) |
|---------|------------------|-------------------|
| Dominio base | `https://m.me` | `https://ig.me` |
| Parámetro | `?ref=` | `?r=` |
| Mensaje pre-llenado | ❌ (usuario escribe) | ✅ (botón "Send" con texto) |
| App destino | Messenger app/web | Instagram app/web |
| Tracking | `messaging_referral` webhook | `messaging_referral` webhook (Instagram) |
| Disponible en | Pages | Instagram Business accounts |

## 🔐 Seguridad del parámetro ref

El `ref` es INPUT DEL USUARIO (viene de un link). **SIEMPRE sanitizar antes de usar en queries o logs**:

```php
// En MessengerLinkService::generateMmeLink()
$cleanRef = preg_replace('/[^a-zA-Z0-9_=\-]/', '', $ref);

// Limitar longitud
$cleanRef = substr($cleanRef, 0, 2083);

// Nunca pasar directo a SQL
DB::table('campaigns')->where('ref', $cleanRef)->get();  // OK después de sanitizar
```

