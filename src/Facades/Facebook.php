<?php

namespace ScriptDevelop\InstagramApiManager\Facades;

use Illuminate\Support\Facades\Facade;

class Facebook extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'facebook';
    }

    public static function account()
    {
        return app('facebook.account');
    }

    public static function message()
    {
        return app('facebook.message');
    }

    public static function profile(): \ScriptDevelop\InstagramApiManager\Services\MessengerProfileService
    {
        return app('facebook.profile');
    }

    public static function link(): \ScriptDevelop\InstagramApiManager\Services\MessengerLinkService
    {
        return app('facebook.link');
    }

    public static function insights(): \ScriptDevelop\InstagramApiManager\Services\MessengerInsightsService
    {
        return app('facebook.insights');
    }

    public static function handover(): \ScriptDevelop\InstagramApiManager\Services\MessengerHandoverService
    {
        return app('facebook.handover');
    }
}
