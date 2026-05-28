<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class MetaApp extends Model
{
    use SoftDeletes, GeneratesUlid;

    protected $table = 'meta_apps';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'app_id',
        'app_secret',
        'verify_token',
        'app_access_token',
        'webhook_fields',
        'is_active',
    ];

    protected $casts = [
        'webhook_fields' => 'array',
        'is_active' => 'boolean',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    // Cifrar app_access_token automáticamente al guardar
    public function setAppAccessTokenAttribute($value)
    {
        if ($value !== null) {
            $this->attributes['app_access_token'] = encrypt($value);
        }
    }

    // Descifrar app_access_token automáticamente al obtener
    public function getAppAccessTokenAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    //Cifrar verify_token automáticamente al guardar
    public function setVerifyTokenAttribute($value)
    {
        if ($value !== null) {
            $this->attributes['verify_token'] = encrypt($value);
        }
    }

    //Descrifrar verify_token automáticamente al obtener
    public function getVerifyTokenAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    //Cifrar app_secret automáticamente al guardar
    public function setAppSecretAttribute($value)
    {
        if ($value !== null) {
            $this->attributes['app_secret'] = encrypt($value);
        }
    }

    //Descifrar app_secret automáticamente al obtener
    public function getAppSecretAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    public function facebookPages(): HasMany
    {
        return $this->hasMany(config('instagram.models.facebook_page'), 'meta_app_id', 'id');
    }

    public function instagramBusinessAccounts()
    {
        return $this->hasManyThrough(
            config('instagram.models.instagram_business_account'),
            config('instagram.models.facebook_page'),
            'meta_app_id', // Foreign key on FacebookPage table
            'facebook_page_id', // Foreign key on InstagramBusinessAccount table
            'id', // Local key on MetaApp table
            'page_id' // Local key on FacebookPage table
        );
    }
}
