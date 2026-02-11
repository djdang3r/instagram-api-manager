<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class InstagramContact extends Model
{
    use SoftDeletes, GeneratesUlid;

    protected $table = 'instagram_contacts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'instagram_business_account_id',
        'instagram_user_id',
        'username',
        'name',
        'profile_picture',
        'last_interaction_at',
        'is_verified_user',
        'follower_count',
        'is_user_follow_business',
        'is_business_follow_user',
        'profile_synced_at',
    ];

    protected $casts = [
        'last_interaction_at' => 'datetime',
        'profile_synced_at' => 'datetime',
        'is_verified_user' => 'boolean',
        'is_user_follow_business' => 'boolean',
        'is_business_follow_user' => 'boolean',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'last_interaction_at', 'profile_synced_at'];

    public function instagramBusinessAccount(): BelongsTo
    {
        return $this->belongsTo(InstagramBusinessAccount::class, 'instagram_business_account_id', 'instagram_business_account_id');
    }
}
