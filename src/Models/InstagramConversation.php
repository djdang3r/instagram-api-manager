<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class InstagramConversation extends Model
{
    use GeneratesUlid;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'instagram_business_account_id',
        'conversation_id',
        'instagram_user_id',
        'last_message_at',
    ];

    protected $dates = ['last_message_at'];

    // Relaciones
    public function businessAccount()
    {
        return $this->belongsTo(InstagramBusinessAccount::class, 'instagram_business_account_id', 'instagram_business_account_id');
    }
    
    public function messages()
    {
        return $this->hasMany(InstagramMessage::class, 'conversation_id', 'id');
    }
}
