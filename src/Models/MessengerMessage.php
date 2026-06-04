<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'message_context',
        'message_context_id',
        'quick_reply_payload',
        'postback_payload',
        'template_payload',
        'json_content',
        'attachments',
        'reactions',
        'caption',
        'status',
        'sent_at',
        'delivered_at',
        'read_at',
        'edited_at',
        'failed_at',
        'created_time',
        'code_error',
        'title_error',
        'message_error',
        'details_error',
        'json',
    ];

    protected $casts = [
        'json_content' => 'array',
        'json' => 'array',
        'attachments' => 'array',
    ];

    protected $dates = [
        'sent_at', 'delivered_at', 'read_at', 'edited_at', 'failed_at', 'created_at', 'updated_at', 'deleted_at',
    ];

    public function messengerConversation(): BelongsTo
    {
        return $this->belongsTo(config('instagram.models.messenger_conversation'), 'conversation_id', 'id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(config('instagram.models.messenger_media_message'), 'message_id', 'message_id');
    }

    public function mediaCount(): int
    {
        return $this->media()->count();
    }

    public function replies()
    {
        // Relación uno a muchos: este mensaje tiene múltiples réplicas
        return $this->hasMany(config('instagram.models.messenger_message'), 'message_id', 'message_context_id');
    }
}
