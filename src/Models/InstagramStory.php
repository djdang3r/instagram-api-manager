<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class InstagramStory extends Model
{
    use GeneratesUlid;

    protected $table = 'instagram_stories';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'story_id',
        'instagram_business_account_id',
        'media_id',
        'media_type',
        'media_url',
        'thumbnail_url',
        'timestamp',
        'expires_at',
        'impressions',
        'reach',
        'replies_count',
        'likes_count',
        'status',
        'mentions',
        'hashtags',
        'raw_data',
    ];

    protected $casts = [
        'impressions' => 'integer',
        'reach' => 'integer',
        'replies_count' => 'integer',
        'likes_count' => 'integer',
        'timestamp' => 'datetime',
        'expires_at' => 'datetime',
        'mentions' => 'array',
        'hashtags' => 'array',
        'raw_data' => 'array',
    ];

    protected $dates = ['created_at', 'updated_at', 'timestamp', 'expires_at'];

    public function instagramBusinessAccount(): BelongsTo
    {
        return $this->belongsTo(
            config('instagram.models.instagram_business_account'),
            'instagram_business_account_id',
            'instagram_business_account_id'
        );
    }
}