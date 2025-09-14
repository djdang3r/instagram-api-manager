<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class MessengerConversation extends Model
{
    use SoftDeletes, GeneratesUlid;

    protected $table = 'messenger_conversations';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'page_id',
        'conversation_id',
        'messenger_user_id',
        'last_message_at',
    ];

    protected $dates = ['last_message_at', 'created_at', 'updated_at', 'deleted_at'];

    public function facebookPage(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'page_id', 'page_id');
    }

    public function messengerMessages(): HasMany
    {
        return $this->hasMany(MessengerMessage::class, 'conversation_id', 'id');
    }
}
