<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class FacebookPost extends Model
{
    use GeneratesUlid;

    protected $table = 'facebook_posts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'post_id',
        'page_id',
        'message',
        'link',
        'created_time',
        'updated_time',
        'like_count',
        'comments_count',
        'shares_count',
        'views_count',
        'status',
        'scheduled_at',
        'published_at',
        'type',
        'raw_data',
    ];

    protected $casts = [
        'like_count' => 'integer',
        'comments_count' => 'integer',
        'shares_count' => 'integer',
        'views_count' => 'integer',
        'created_time' => 'datetime',
        'updated_time' => 'datetime',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'raw_data' => 'array',
    ];

    protected $dates = ['created_at', 'updated_at', 'created_time', 'updated_time', 'scheduled_at', 'published_at'];

    public function facebookPage(): BelongsTo
    {
        return $this->belongsTo(config('instagram.models.facebook_page'), 'page_id', 'page_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(config('instagram.models.facebook_comment'), 'post_id', 'post_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(config('instagram.models.facebook_media'), 'facebook_post_id', 'id');
    }
}