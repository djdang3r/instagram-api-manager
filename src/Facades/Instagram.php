<?php

namespace ScriptDevelop\InstagramApiManager\Facades;

use Illuminate\Support\Facades\Facade;

class Instagram extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'instagram'; // Llave general, opcional
    }

    public static function account()
    {
        return app('instagram.account');
    }

    public static function message()
    {
        return app('instagram.message');
    }
}
