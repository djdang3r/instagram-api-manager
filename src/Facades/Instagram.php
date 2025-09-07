<?php

namespace ScriptDevelop\InstagramApiManager\Facades;

use Illuminate\Support\Facades\Facade;
use ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount;

/**
 * @method static \ScriptDevelop\InstagramApiManager\Services\InstagramAccountService forAccount(InstagramBusinessAccount $account)
 * @method static \ScriptDevelop\InstagramApiManager\Services\InstagramAccountService forAccountId(string $accountId)
 * @method static string getAuthorizationUrl(array $scopes = [], ?string $state = null)
 * @method static \ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount|null handleCallback(string $code, ?string $state = null)
 * @method static array|null exchangeForLongLivedToken(string $shortLivedToken)
 * @method static array|null refreshLongLivedToken(string $longLivedToken)
 * @method static bool hasPermission(\ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount $account, string $permission)
 * @method static array|null getProfileInfo(string|null $accessToken = null)
 * @method static array|null getUserMedia(string|null $userId = null, string|null $accessToken = null)
 * @method static array|null getMediaDetails(string $mediaId, string|null $accessToken = null)
 * @method static bool linkWithFacebookPage(string $instagramAccountId, string $facebookPageId)
 */
class Instagram extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'instagram'; // Llave general, opcional
    }

    /**
     * Método helper para facilitar el uso
     */
    public static function account(InstagramBusinessAccount|string|null $account = null): \ScriptDevelop\InstagramApiManager\Services\InstagramAccountService
    {
        $service = app('instagram.account');
        
        if ($account instanceof InstagramBusinessAccount) {
            return $service->forAccount($account);
        }
        
        if (is_string($account)) {
            return $service->forAccountId($account);
        }
        
        return $service;
    }

    public static function message()
    {
        return app('instagram.message');
    }

    public static function media()
    {
        return app('instagram.media');
    }
}
