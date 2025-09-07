<?php

namespace ScriptDevelop\InstagramApiManager\Facades;

use Illuminate\Support\Facades\Facade;
use ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount;

/**
 * @method static \ScriptDevelop\InstagramApiManager\Services\InstagramAccountService forAccount(InstagramBusinessAccount $account)
 * @method static \ScriptDevelop\InstagramApiManager\Services\InstagramAccountService forAccountId(string $accountId)
 * @method static array|null getProfileInfo(string|null $accessToken = null)
 * @method static array|null getUserMedia(string|null $userId = null, string|null $accessToken = null)
 * @method static array|null getMediaDetails(string $mediaId, string|null $accessToken = null)
 * @method static \ScriptDevelop\InstagramApiManager\Services\InstagramAccountService account(InstagramBusinessAccount|string|null $account = null)
 */
class Instagram extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'instagram.account';
    }

    /**
     * Proxy para el método forAccount del servicio.
     */
    public static function forAccount(InstagramBusinessAccount $account)
    {
        return self::getFacadeRoot()->forAccount($account);
    }

    /**
     * Proxy para el método forAccountId del servicio.
     */
    public static function forAccountId(string $accountId)
    {
        return self::getFacadeRoot()->forAccountId($accountId);
    }

    /**
     * Método helper para facilitar el uso.
     */
    public static function account(InstagramBusinessAccount|string|null $account = null): \ScriptDevelop\InstagramApiManager\Services\InstagramAccountService
    {
        $service = self::getFacadeRoot();
        
        if ($account instanceof InstagramBusinessAccount) {
            return $service->forAccount($account);
        }
        
        if (is_string($account)) {
            return $service->forAccountId($account);
        }
        
        return $service;
    }
}