<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class FacebookPage extends Model
{
    use SoftDeletes, GeneratesUlid;

    protected $table = 'meta_facebook_pages';

    protected $primaryKey = 'page_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'page_id',
        'meta_app_id',
        'name',
        'access_token',
        'tasks',
        'is_active',
    ];

    protected $casts = [
        'tasks' => 'array',
        'is_active' => 'boolean',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function setAccessTokenAttribute($value)
    {
        $this->attributes['access_token'] = $value ? encrypt($value) : null;
    }

    public function getAccessTokenAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    public function metaApp(): BelongsTo
    {
        return $this->belongsTo(MetaApp::class, 'meta_app_id', 'id');
    }

    public function instagramBusinessAccount(): HasOne
    {
        return $this->hasOne(InstagramBusinessAccount::class, 'facebook_page_id', 'page_id');
    }

    public function messengerConversations(): HasMany
    {
        return $this->hasMany(MessengerConversation::class, 'page_id', 'page_id');
    }

    public function messengerContacts(): HasMany
    {
        return $this->hasMany(MessengerContact::class, 'page_id', 'page_id');
    }
}
