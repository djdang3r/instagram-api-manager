<?php

namespace ScriptDevelop\InstagramApiManager\Traits;

use Illuminate\Support\Str;

trait GeneratesUlid
{
    protected static function bootGeneratesUlid()
    {
        static::creating(function ($model) {
            $keyName = $model->getKeyName();
            if (empty($model->{$keyName})) {
                $model->{$keyName} = Str::ulid()->toString();
            }
        });
    }
}