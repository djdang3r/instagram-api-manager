<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class InstagramContact extends Model
{
    use SoftDeletes, GeneratesUlid;

    protected $table = 'meta_instagram_contacts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'instagram_business_account_id',
        'instagram_user_id',
        'username',
        'profile_picture',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function instagramBusinessAccount(): BelongsTo
    {
        return $this->belongsTo(InstagramBusinessAccount::class, 'instagram_business_account_id', 'instagram_business_account_id');
    }
}
