[◄◄ Configuration](02-configuration.md)
[▲ Table of Contents](00-table-of-contents.md)
[Message Management ►►](04-messages.md)

# 👤 Account and Profile Management

The package facilitates obtaining accounts and managing Instagram Business profiles.

### 1. Authentication Flow (OAuth)

To get the authorization URL and allow a user to link their Instagram account:

```php
use ScriptDevelop\InstagramApiManager\Facades\Instagram;

$url = Instagram::account()->getAuthorizationUrl();

return redirect($url);
```

### 2. Linking a Specific Account

Once you have the token or stored account, you can initialize the service to interact with it:

```php
use ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount;
use ScriptDevelop\InstagramApiManager\Facades\Instagram;

// By model
$account = InstagramBusinessAccount::first();
$profile = Instagram::forAccount($account)->getProfileInfo();

// By account ID (IGSID)
$media = Instagram::account('17918115224312316')->getUserMedia();
```

### 3. Data Synchronization

The package uses Eloquent models to persist information. You can use the internal `ApiClient` if you need to perform custom queries or manually synchronize managed pages:

```php
$pages = Instagram::account()->getUserManagedPages($accessToken);
```

### 4. Available Models

- `InstagramBusinessAccount`: Represents the Meta business account linked to Instagram.
- `InstagramProfile`: Stores public user profile details.
- `InstagramContact`: Manages Instagram users interacting with your account.

---
[◄◄ Configuration](02-configuration.md) | [Message Management ►►](04-messages.md)
