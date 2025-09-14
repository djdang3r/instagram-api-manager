<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class InstagramMessage extends Model
{
    use SoftDeletes, GeneratesUlid;

    protected $table = 'meta_instagram_messages';

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
        'message_context_from',
        'context_message_id',
        'context_message_text',
        'quick_reply_payload',
        'postback_payload',
        'template_payload',
        'attachments',
        'caption',
        'media_url',
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
        'created_time',
        'is_unsupported',
        'reactions',
    ];

    protected $casts = [
        'attachments' => 'array',
        'json_content' => 'array',
        'json' => 'array',
        'reactions' => 'array',
        'created_time' => 'datetime',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'edited_at' => 'datetime',
        'failed_at' => 'datetime',
        'is_unsupported' => 'boolean',
    ];

    protected $dates = [
        'created_time', 'sent_at', 'read_at', 'edited_at', 'failed_at', 
        'created_at', 'updated_at', 'deleted_at',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(InstagramConversation::class, 'conversation_id', 'id');
    }

    public function getSenderAttribute()
    {
        return $this->message_from;
    }

    public function getRecipientAttribute()
    {
        return $this->message_to;
    }
}