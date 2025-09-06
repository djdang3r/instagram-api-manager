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

    public function facebookPages(): HasMany
    {
        return $this->hasMany(FacebookPage::class, 'meta_app_id', 'id');
    }
}
