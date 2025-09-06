<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class InstagramContact extends Model
{
    use GeneratesUlid;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'instagram_business_account_id',
        'instagram_user_id',
        'username',
        'profile_picture',
    ];

    // Relaciones
    public function businessAccount()
    {
        return $this->belongsTo(InstagramBusinessAccount::class, 'instagram_business_account_id', 'instagram_business_account_id');
    }
}
