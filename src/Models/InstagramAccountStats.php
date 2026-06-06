<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class InstagramAccountStats extends Model
{
    use GeneratesUlid;

    protected $table = 'instagram_account_stats';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'instagram_business_account_id',
        'date',
        'followers_count',
        'following_count',
        'media_count',
        'total_messages_sent',
        'total_messages_received',
        'total_comments',
        'total_followers_gained',
        'total_followers_lost',
        'raw_data',
    ];

    protected $casts = [
        'followers_count' => 'integer',
        'following_count' => 'integer',
        'media_count' => 'integer',
        'total_messages_sent' => 'integer',
        'total_messages_received' => 'integer',
        'total_comments' => 'integer',
        'total_followers_gained' => 'integer',
        'total_followers_lost' => 'integer',
        'date' => 'date',
        'raw_data' => 'array',
    ];

    protected $dates = ['created_at', 'updated_at', 'date'];

    public function instagramBusinessAccount(): BelongsTo
    {
        return $this->belongsTo(
            config('instagram.models.instagram_business_account'),
            'instagram_business_account_id',
            'instagram_business_account_id'
        );
    }
}