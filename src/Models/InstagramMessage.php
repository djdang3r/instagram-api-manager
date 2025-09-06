<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class InstagramMessage extends Model
{
    use GeneratesUlid;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'conversation_id',
        'message_id',
        'direction',
        'message_type',
        'content',
        'media_url',
        'sent_at',
    ];

    protected $dates = ['sent_at'];

    // Relaciones
    public function conversation()
    {
        return $this->belongsTo(InstagramConversation::class, 'conversation_id', 'id');
    }
}
