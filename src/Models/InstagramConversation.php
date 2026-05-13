<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class InstagramConversation extends Model
{
    use SoftDeletes, GeneratesUlid;

    protected $table = 'instagram_conversations';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'instagram_business_account_id',
        'conversation_id',
        'instagram_user_id',
        'senders',
        'updated_time',
        'unread_count',
        'is_archived',
        'last_message_at',
    ];

    protected $casts = [
        'senders' => 'array',
        'updated_time' => 'datetime',
        'is_archived' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    protected $dates = [
        'last_message_at', 'created_at', 'updated_at', 'deleted_at', 'updated_time'
    ];

    public function instagramBusinessAccount(): BelongsTo
    {
        return $this->belongsTo(
            config('instagram.models.instagram_business_account'),
            'instagram_business_account_id',
            'instagram_business_account_id'
        );
    }

    public function messages(): HasMany
    {
        return $this->hasMany(config('instagram.models.instagram_message'), 'conversation_id', 'id');
    }

    public function latestMessage()
    {
        return $this->hasOne(config('instagram.models.instagram_message'), 'conversation_id', 'id')
            ->orderBy('created_at', 'desc');
    }

    public function contact()
    {
        return $this->belongsTo(
            config('instagram.models.instagram_contact'),
            'instagram_user_id',
            'instagram_user_id'
        );
    }
}