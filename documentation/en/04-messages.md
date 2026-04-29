[◄◄ Accounts](03-accounts.md)
[▲ Table of Contents](00-table-of-contents.md)
[Persistent Menu ►►](05-persistent-menu.md)

# 💬 Message Management

Easily send and receive text messages, media, and interactive elements.

### 1. Sending Text Messages

```php
use ScriptDevelop\InstagramApiManager\Facades\Instagram;

$result = Instagram::message()
    ->withAccessToken($account->access_token)
    ->withInstagramUserId($account->instagram_business_account_id)
    ->sendTextMessage('RECIPIENT_IGSID', 'Hello, how can we help you?');
```

### 2. Sending Media

```php
// Send Image
$result = Instagram::message()
    ->sendImageMessage('RECIPIENT_IGSID', 'https://your-site.com/image.jpg');

// Send Sticker
$result = Instagram::message()
    ->sendStickerMessage('RECIPIENT_IGSID');
```

### 3. Quick Replies

Quick replies allow the user to choose from a list of options:

```php
$quickReplies = [
    ['content_type' => 'text', 'title' => 'Sales', 'payload' => 'SALES_REQ'],
    ['content_type' => 'text', 'title' => 'Support', 'payload' => 'SUPPORT_REQ']
];

$result = Instagram::message()
    ->sendQuickReplies('RECIPIENT_IGSID', 'Select a department:', $quickReplies);
```

### 4. Generic Templates

Templates allow sending cards with images, subtitles, and multiple buttons:

```php
$elements = [
    [
        'title' => 'Star Product',
        'image_url' => 'https://example.com/p1.jpg',
        'subtitle' => 'Check out our current offers',
        'buttons' => [
            [
                'type' => 'web_url',
                'url' => 'https://example.com/shop',
                'title' => 'Visit Shop'
            ],
            [
                'type' => 'postback',
                'title' => 'Talk to Agent',
                'payload' => 'AGENT_REQ'
            ]
        ]
    ]
];

$result = Instagram::message()->sendGenericTemplate('RECIPIENT_IGSID', $elements);
```

### 5. Reactions

You can react to specific messages:

```php
$result = Instagram::message()->reactToMessage('RECIPIENT_IGSID', 'MESSAGE_ID', 'love'); // ❤️
```

---
[◄◄ Accounts](03-accounts.md) | [Persistent Menu ►►](05-persistent-menu.md)
