[◄◄ Home](../../README.md)
[▲ Table of Contents](00-table-of-contents.md)
[Configure API ►►](02-configuration.md)

# 🚀 Installation

This package is specifically designed for Laravel 12.x and requires PHP 8.2+.

### 1. Prerequisites

Before starting, make sure you have:
- A Facebook Developer account.
- A Meta App configured with **Instagram Graph API** and **Messenger API for Instagram** products.
- An Instagram Business account linked to a Facebook Fan Page.

### 2. Installation via Composer

Run the following command in your terminal:

```bash
composer require scriptdevelop/instagram-api-manager
```

### 3. Publish Resources

You can publish all the package resources (configuration, migrations, routes, etc.) with a single command:

```bash
php artisan vendor:publish --tag=instagram-api-manager
```

Or if you prefer more granular control:

```bash
# Publish only migrations
php artisan vendor:publish --tag=instagram-migrations

# Publish only configuration
php artisan vendor:publish --tag=instagram-config

# Publish only callback/webhook routes
php artisan vendor:publish --tag=instagram-callback-routes
php artisan vendor:publish --tag=instagram-webhook-routes

# Publish only configured logging
php artisan vendor:publish --tag=instagram-logging
```

### 4. Run Migrations

Create the necessary tables in your database:

```bash
php artisan migrate
```

### 5. Webhook Configuration

Add the verification token to your `.env` file:

```env
INSTAGRAM_WEBHOOK_VERIFY_TOKEN=your_custom_secure_token
```

> [!IMPORTANT]
> Don't forget to exclude the webhook route from CSRF middleware in `bootstrap/app.php` (Laravel 11+) or `VerifyCsrfToken.php` (earlier versions).

### 6. Installation Wizard

Since v1.0.78, the package includes an interactive installation assistant:

```bash
php artisan instagram:install
```

The wizard guides you step by step through:
1. Publishing configuration files
2. Optional migration execution
3. Storage symlink creation
4. Automatic CSRF exclusion in `bootstrap/app.php`
5. Optional route publishing
6. Required environment variables printout

For non-interactive use in production, skip prompts with `--no-interaction` or use `--force` to overwrite existing configs.

### 7. Manual Installation (step by step)

If you prefer not to use the wizard, here's the detail:

```bash
# 1. Install the package
composer require scriptdevelop/instagram-api-manager

# 2. Publish configs
php artisan vendor:publish --tag=instagram-facebook-config

# 3. Publish migrations
php artisan vendor:publish --tag=instagram-migrations

# 4. Publish webhook routes
php artisan vendor:publish --tag=instagram-webhook-routes
php artisan vendor:publish --tag=facebook-webhook-routes

# 5. Publish broadcast channels (Reverb)
php artisan vendor:publish --tag=instagram-channels

# 6. Create storage symlink for media
php artisan storage:link

# 7. Run migrations
php artisan migrate

# 8. Add environment variables
# (see .env.example generated in step 2)

# 9. Exclude CSRF in bootstrap/app.php (Laravel 11+):
# $middleware->validateCsrfTokens(except: [
#     'instagram-webhook/*',
#     'facebook-webhook/*',
#     'instagram/callback',
#     'facebook/callback',
# ]);
```

### 8. Post-Installation Verification

```bash
# Verify commands are registered
php artisan list | grep -E "instagram:|messenger:"

# Verify routes are loaded
php artisan route:list | grep -E "webhook|callback"

# Verify migrations are available
php artisan migrate:status
```

If everything appears correctly, the package is ready. Proceed to [02-configuration.md](02-configuration.md) to connect with Meta.

---
[◄◄ Home](../../README.md) | [Configure API ►►](02-configuration.md)
