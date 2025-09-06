<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class InstagramProfile extends Model
{
    use GeneratesUlid;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'instagram_business_account_id',
        'profile_name',
        'profile_picture',
        'bio',
    ];

    // Relaciones
    public function businessAccount()
    {
        return $this->belongsTo(InstagramBusinessAccount::class, 'instagram_business_account_id', 'instagram_business_account_id');
    }
}
