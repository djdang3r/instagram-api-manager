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
}
