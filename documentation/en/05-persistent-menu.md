[◄◄ Messages](04-messages.md)
[▲ Table of Contents](00-table-of-contents.md)
[Links and QR ►►](06-links.md)

# 🛠️ Persistent Menu and Ice Breakers

Configure the automated experience for your users in Instagram Direct.

### 1. Persistent Menu

 The persistent menu is a menu that is always available in the Instagram chat interface.

```php
use ScriptDevelop\InstagramApiManager\Facades\Instagram;

// Create buttons
$buttons = [
    Instagram::persistentMenu()->createPostbackButton('View Products', 'VIEW_PRODUCTS'),
    Instagram::persistentMenu()->createUrlButton('Visit Website', 'https://shop.com', 'full')
];

// Create the localized menu (default is mandatory)
$menu = Instagram::persistentMenu()->createLocalizedMenu('default', false, $buttons);

// Set the menu
$result = Instagram::persistentMenu()
    ->withAccessToken($account->access_token)
    ->withInstagramUserId($account->instagram_business_account_id)
    ->setPersistentMenu([$menu]);
```

### 2. Ice Breakers

Ice Breakers are questions that appear to users who have never started a conversation with your account.

```php
// Create actions
$actions = [
    Instagram::persistentMenu()->createIceBreakerAction('What are your hours?', 'HOURS_REQ'),
    Instagram::persistentMenu()->createIceBreakerAction('Technical Support', 'SUPPORT_REQ')
];

// Create ice breaker
$iceBreaker = Instagram::persistentMenu()->createIceBreaker('default', $actions);

// Set ice breakers
$result = Instagram::persistentMenu()
    ->withAccessToken($account->access_token)
    ->withInstagramUserId($account->instagram_business_account_id)
    ->setIceBreakers([$iceBreaker]);
```

### 3. Management (Get / Delete)

You can check or delete these settings at any time:

```php
// Get current menu
$currentMenu = Instagram::persistentMenu()->getPersistentMenu();

// Delete Ice Breakers
$result = Instagram::persistentMenu()->deleteIceBreakers();
```

---
[◄◄ Messages](04-messages.md) | [Links and QR ►►](06-links.md)
