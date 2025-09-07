<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class InstagramProfile extends Model
{
    use SoftDeletes, GeneratesUlid;

    protected $table = 'instagram_profiles';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'instagram_business_account_id',
        'profile_name',
        'username',
        'profile_picture',
        'bio',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function instagramBusinessAccount(): BelongsTo
    {
        // return $this->belongsTo(InstagramBusinessAccount::class, 'instagram_business_account_id', 'instagram_business_account_id');
        return $this->belongsTo(InstagramBusinessAccount::class, 'instagram_business_account_id', 'instagram_business_account_id');
    }
}
