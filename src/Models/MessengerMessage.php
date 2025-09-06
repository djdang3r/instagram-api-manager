<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class MessengerMessage extends Model
{
    use SoftDeletes, GeneratesUlid;

    protected $table = 'messenger_messages';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'conversation_id',
        'message_id',
        'message_method',
        'message_type',
        'message_from',
        'message_to',
        'message_content',
        'json_content',
        'status',
        'sent_at',
        'read_at',
        'edited_at',
        'failed_at',
        'code_error',
        'title_error',
        'message_error',
        'details_error',
        'json',
    ];

    protected $casts = [
        'json_content' => 'array',
        'json' => 'array',
    ];

    protected $dates = [
        'sent_at', 'read_at', 'edited_at', 'failed_at', 'created_at', 'updated_at', 'deleted_at',
    ];

    public function messengerConversation(): BelongsTo
    {
        return $this->belongsTo(MessengerConversation::class, 'conversation_id', 'id');
    }
}
