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
        'tasks',
    ];

    protected $casts = [
        'tasks' => 'array',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

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
