<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class InstagramMediaStats extends Model
{
    use GeneratesUlid;

    protected $table = 'instagram_media_stats';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'instagram_media_id',
        'instagram_business_account_id',
        'date',
        'impressions',
        'reach',
        'likes',
        'comments',
        'saves',
        'shares',
        'video_views',
        'profile_visits',
        'follows',
        'raw_data',
    ];

    protected $casts = [
        'impressions' => 'integer',
        'reach' => 'integer',
        'likes' => 'integer',
        'comments' => 'integer',
        'saves' => 'integer',
        'shares' => 'integer',
        'video_views' => 'integer',
        'profile_visits' => 'integer',
        'follows' => 'integer',
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