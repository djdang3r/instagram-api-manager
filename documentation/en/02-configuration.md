[◄◄ Installation](01-installation.md)
[▲ Table of Contents](00-table-of-contents.md)
[Account Management ►►](03-accounts.md)

# 🧩 API Configuration

For the package to function correctly, you must configure your Meta credentials in your `.env` file.

### 1. Environment Variables

Add and adapt the following variables to your `.env` file:

```env
# Instagram API
INSTAGRAM_CLIENT_ID=your_instagram_client_id
INSTAGRAM_CLIENT_SECRET=your_instagram_client_secret
INSTAGRAM_REDIRECT_URI=https://your-domain.com/instagram/callback
INSTAGRAM_API_BASE_URL=https://graph.instagram.com
INSTAGRAM_API_VERSION=v23.0
INSTAGRAM_API_TIMEOUT=30
INSTAGRAM_API_RETRY_ATTEMPTS=3
INSTAGRAM_WEBHOOK_VERIFY_TOKEN=your_custom_secure_token

# Facebook API (Required for Messenger/Pages/Advanced OAuth)
FACEBOOK_CLIENT_ID=your_facebook_client_id
FACEBOOK_CLIENT_SECRET=your_facebook_client_secret
FACEBOOK_REDIRECT_URI=https://your-domain.com/facebook/callback
FACEBOOK_API_BASE_URL=https://graph.facebook.com
FACEBOOK_API_VERSION=v23.0
```

### 2. Logging Configuration (Optional but Recommended)

The package includes a custom log channel for Instagram. To activate it, add the following channel to your `config/logging.php` file:

```php
'channels' => [
    // ... other channels
    'instagram' => [
        'driver' => 'daily',
        'path' => storage_path('logs/instagram.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
        'tap' => [\ScriptDevelop\InstagramApiManager\Logging\CustomizeFormatter::class],
    ],
    
    'facebook' => [
        'driver' => 'daily',
        'path' => storage_path('logs/facebook.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
        'tap' => [\ScriptDevelop\InstagramApiManager\Logging\CustomizeFormatter::class],
    ],
],

## 🆕 New in v1.1.0

The following env vars are new in v1.1.0:

```env
# Facebook OAuth — Custom Redirect URLs
FACEBOOK_CUSTOM_REDIRECT_SUCCESS_URL=https://your-domain.com/oauth/success
FACEBOOK_CUSTOM_REDIRECT_ERROR_URL=https://your-domain.com/oauth/error
FACEBOOK_CUSTOM_REDIRECT_WARNING_URL=https://your-domain.com/oauth/warning

# Facebook Webhook Rate Limiting
FACEBOOK_WEBHOOK_RATE_LIMIT_MAX_ATTEMPTS=60
FACEBOOK_WEBHOOK_RATE_LIMIT_DECAY_MINUTES=1

# Facebook Broadcast (per-message delivery mode)
FACEBOOK_BROADCAST_DELIVERY_PER_MESSAGE=false

# Instagram Custom Redirect URLs (mirror)
INSTAGRAM_CUSTOM_REDIRECT_SUCCESS_URL=https://your-domain.com/oauth/success
INSTAGRAM_CUSTOM_REDIRECT_ERROR_URL=https://your-domain.com/oauth/error
INSTAGRAM_CUSTOM_REDIRECT_WARNING_URL=https://your-domain.com/oauth/warning

# Facebook Media
FACEBOOK_MEDIA_DISK=public
FACEBOOK_MEDIA_PATH=facebook
FACEBOOK_MME_BASE_URL=https://m.me
FACEBOOK_DOWNLOAD_USER_PROFILE_PICTURE=false
```
```

### 3. Configuration File

If you published the configuration, you will see a file at `config/instagram.php`. Here you can adjust default values and the models the package will use to resolve Instagram entities.

```php
return [
    'models' => [
        'business_account' => \ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount::class,
        'profile' => \ScriptDevelop\InstagramApiManager\Models\InstagramProfile::class,
        'conversation' => \ScriptDevelop\InstagramApiManager\Models\InstagramConversation::class,
        'message' => \ScriptDevelop\InstagramApiManager\Models\InstagramMessage::class,
        'contact' => \ScriptDevelop\InstagramApiManager\Models\InstagramContact::class,
    ],
    // ...
];
```

---
[◄◄ Installation](01-installation.md) | [Account Management ►►](03-accounts.md)
