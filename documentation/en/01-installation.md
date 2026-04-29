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

---
[◄◄ Home](../../README.md) | [Configure API ►►](02-configuration.md)
