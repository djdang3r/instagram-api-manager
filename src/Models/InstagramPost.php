<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class InstagramPost extends Model
{
    use GeneratesUlid;

    protected $table = 'instagram_posts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'media_id',
        'instagram_business_account_id',
        'caption',
        'media_type',
        'media_url',
        'permalink',
        'thumbnail_url',
        'timestamp',
        'username',
        'like_count',
        'comments_count',
        'status',
        'scheduled_at',
        'published_at',
        'product_type',
        'children_ids',
        'raw_data',
    ];

    protected $casts = [
        'like_count' => 'integer',
        'comments_count' => 'integer',
        'timestamp' => 'datetime',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'children_ids' => 'array',
        'raw_data' => 'array',
    ];

    protected $dates = ['created_at', 'updated_at', 'timestamp', 'scheduled_at', 'published_at'];

    public function instagramBusinessAccount(): BelongsTo
    {
        return $this->belongsTo(
            config('instagram.models.instagram_business_account'),
            'instagram_business_account_id',
            'instagram_business_account_id'
        );
    }

    public function mediaPosts(): HasMany
    {
        return $this->hasMany(config('instagram.models.instagram_media_post'), 'instagram_post_id', 'id');
    }
}