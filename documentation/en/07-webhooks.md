[◄◄ Links and QR](06-links.md)
[▲ Table of Contents](00-table-of-contents.md)

# 📡 Webhooks and Events

The package automatically handles verification and reception of Instagram events.

### 1. Webhook Route

When publishing routes, it will be automatically registered:
- `POST /instagram-webhook`: To receive notifications.
- `GET /instagram-webhook`: For Meta verification.

### 2. Message Processing

The included `InstagramWebhookController` handles receiving the payload. If you want to delegate the logic, the package triggers internal processes that you can capture.

Make sure your models are correctly configured in `config/instagram.php` so the system knows where to save incoming messages.

### 3. Debug Logging

Starting from version `1.0.60`, the system includes detailed logs in `storage/logs/instagram.log` that allow you to monitor the incoming flow:

- ✅ Sender identification.
- ✅ Storing the message in the database.
- ✅ Managing states (read, delivered).

### 4. Customization

If you need very specific logic, you can override the controller or simply extend the `InstagramMessageService` service to add your own processing hooks.

```php
// In your own ServiceProvider or Controller
use ScriptDevelop\InstagramApiManager\Services\InstagramMessageService;

$service = app(InstagramMessageService::class);
$service->processWebhookMessage($payload);
```

---
[◄◄ Links and QR](06-links.md) | [▲ Table of Contents](00-table-of-contents.md)
