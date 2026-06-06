<?php

namespace ScriptDevelop\InstagramApiManager\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use ScriptDevelop\InstagramApiManager\Support\InstagramModelResolver;
use InvalidArgumentException;

/**
 * @method static \ScriptDevelop\InstagramApiManager\Services\InstagramAccountService forAccount(Model $account)
 * @method static \ScriptDevelop\InstagramApiManager\Services\InstagramAccountService forAccountId(string $accountId)
 * @method static array|null getProfileInfo(string|null $accessToken = null)
 * @method static array|null getUserMedia(string|null $userId = null, string|null $accessToken = null)
 * @method static array|null getMediaDetails(string $mediaId, string|null $accessToken = null)
 * @method static \ScriptDevelop\InstagramApiManager\Services\InstagramAccountService account(Model|string|null $account = null)
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
    public static function forAccount(Model $account)
    {
        $configuredModelClass = get_class(InstagramModelResolver::instagram_business_account()->getModel());

        if (!$account instanceof $configuredModelClass) {
            throw new InvalidArgumentException(
                "La cuenta debe ser una instancia de [$configuredModelClass]."
            );
        }

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
    public static function account(Model|string|null $account = null): \ScriptDevelop\InstagramApiManager\Services\InstagramAccountService
    {
        $service = self::getFacadeRoot();

        if ($account instanceof Model) {
            return $service->forAccount($account);
        }

        if (is_string($account)) {
            return $service->forAccountId($account);
        }

        return $service;
    }

    /**
     * Obtener el servicio de mensajes
     */
    public static function message(): \ScriptDevelop\InstagramApiManager\Services\InstagramMessageService
    {
        return app('instagram.message');
    }

    /**
     * Obtener el servicio de menú persistente
     */
    public static function persistentMenu(): \ScriptDevelop\InstagramApiManager\Services\InstagramPersistentMenuService
    {
        return app('instagram.persistent_menu');
    }

    /**
     * Obtener el servicio de enlaces
     */
    public static function link(): \ScriptDevelop\InstagramApiManager\Services\InstagramLinkService
    {
        return app('instagram.link');
    }

    public static function comment(): \ScriptDevelop\InstagramApiManager\Services\InstagramCommentService
    {
        return app('instagram.comment');
    }

    public static function publishing(): \ScriptDevelop\InstagramApiManager\Services\InstagramContentPublishingService
    {
        return app('instagram.publishing');
    }

    public static function insights(): \ScriptDevelop\InstagramApiManager\Services\InstagramInsightsService
    {
        return app('instagram.insights');
    }
}