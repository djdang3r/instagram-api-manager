<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;

class InstagramBusinessAccount extends Model
{
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

    // Relaciones
    public function profiles()
    {
        return $this->hasMany(InstagramProfile::class, 'instagram_business_account_id', 'instagram_business_account_id');
    }

    public function conversations()
    {
        return $this->hasMany(InstagramConversation::class, 'instagram_business_account_id', 'instagram_business_account_id');
    }

    public function contacts()
    {
        return $this->hasMany(InstagramContact::class, 'instagram_business_account_id', 'instagram_business_account_id');
    }
}
