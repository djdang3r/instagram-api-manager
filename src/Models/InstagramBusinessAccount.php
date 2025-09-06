<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class InstagramBusinessAccount extends Model
{
    use SoftDeletes, GeneratesUlid;

    protected $table = 'instagram_business_accounts';

    protected $primaryKey = 'instagram_business_account_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'instagram_business_account_id',
        'facebook_page_id',
        'name',
        'access_token',
        'token_expires_in',
        'tasks',
    ];

    protected $casts = [
        'tasks' => 'array',
        'token_expires_in' => 'integer',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function setAccessTokenAttribute($value)
    {
        $this->attributes['access_token'] = encrypt($value);
    }

    public function getAccessTokenAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    public function facebookPage(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'facebook_page_id', 'page_id');
    }

    public function instagramProfile(): HasOne
    {
        return $this->hasOne(InstagramProfile::class, 'instagram_business_account_id', 'instagram_business_account_id');
    }

    public function instagramConversations(): HasMany
    {
        return $this->hasMany(InstagramConversation::class, 'instagram_business_account_id', 'instagram_business_account_id');
    }

    public function instagramContacts(): HasMany
    {
        return $this->hasMany(InstagramContact::class, 'instagram_business_account_id', 'instagram_business_account_id');
    }
}
