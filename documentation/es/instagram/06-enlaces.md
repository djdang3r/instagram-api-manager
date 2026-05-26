[◄◄ Menú Persistente](05-menu-persistente.md)
[▲ Tabla de contenido](00-tabla-de-contenido.md)
[Webhooks ►►](07-webhooks.md)

# 🔗 Enlaces ig.me y Códigos QR

Fomenta el inicio de conversaciones con enlaces directos y códigos QR.

### 1. Generar Enlaces ig.me

Los enlaces `ig.me` redirigen al usuario directamente a una conversación contigo en Instagram. Puedes incluir parámetros de referencia para saber de dónde viene el usuario.

```php
use ScriptDevelop\InstagramApiManager\Facades\Instagram;

// Enlace simple
$link = $account->getIgMeLink();

// Enlace con parámetro de referencia (campaign)
$campaignLink = $account->getIgMeLink('verano_2024');
```

### 2. Códigos QR

Genera un código QR que apunta a tu enlace `ig.me`:

```php
// Generar QR de 500x500px con referencia
$qrCode = Instagram::link()->generateIgMeQrCode($account, 'tienda_fisica', 500);

// El resultado es una URL de la imagen generada por Meta
echo '<img src="' . $qrCode . '">';
```

### 3. Estadísticas de Referencias

Si usas parámetros `ref`, puedes consultar cuántas personas han entrado por cada uno:

```php
$stats = Instagram::link()->getReferralStats($account->instagram_business_account_id, 'verano_2024');
```

---
[◄◄ Menú Persistente](05-menu-persistente.md) | [Webhooks ►►](07-webhooks.md)
