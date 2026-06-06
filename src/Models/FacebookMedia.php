<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class FacebookMedia extends Model
{
    use GeneratesUlid;

    protected $table = 'facebook_media';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'media_id',
        'facebook_post_id',
        'page_id',
        'media_type',
        'media_url',
        'thumbnail_url',
        'permalink',
        'name',
        'description',
        'created_time',
        'like_count',
        'comments_count',
        'shares_count',
        'raw_data',
    ];

    protected $casts = [
        'like_count' => 'integer',
        'comments_count' => 'integer',
        'shares_count' => 'integer',
        'created_time' => 'datetime',
        'raw_data' => 'array',
    ];

    protected $dates = ['created_at', 'updated_at', 'created_time'];

    public function facebookPage(): BelongsTo
    {
        return $this->belongsTo(config('instagram.models.facebook_page'), 'page_id', 'page_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(config('instagram.models.facebook_post'), 'facebook_post_id', 'id');
    }
}