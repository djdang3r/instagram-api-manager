<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class InstagramMediaPost extends Model
{
    use GeneratesUlid;

    protected $table = 'instagram_media_posts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'media_id',
        'instagram_post_id',
        'instagram_business_account_id',
        'media_type',
        'media_url',
        'thumbnail_url',
        'permalink',
        'sort_order',
        'caption',
        'timestamp',
        'raw_data',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'timestamp' => 'datetime',
        'raw_data' => 'array',
    ];

    protected $dates = ['created_at', 'updated_at', 'timestamp'];

    public function instagramPost(): BelongsTo
    {
        return $this->belongsTo(config('instagram.models.instagram_post'), 'instagram_post_id', 'id');
    }

    public function instagramBusinessAccount(): BelongsTo
    {
        return $this->belongsTo(
            config('instagram.models.instagram_business_account'),
            'instagram_business_account_id',
            'instagram_business_account_id'
        );
    }
}