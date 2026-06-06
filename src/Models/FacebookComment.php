<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class FacebookComment extends Model
{
    use GeneratesUlid;

    protected $table = 'facebook_comments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'comment_id',
        'page_id',
        'post_id',
        'parent_id',
        'message',
        'from_name',
        'from_id',
        'profile_url',
        'created_time',
        'like_count',
        'hidden_at',
        'deleted_at',
        'raw_data',
    ];

    protected $casts = [
        'like_count' => 'integer',
        'created_time' => 'datetime',
        'hidden_at' => 'datetime',
        'deleted_at' => 'datetime',
        'raw_data' => 'array',
    ];

    protected $dates = ['created_at', 'updated_at', 'created_time', 'hidden_at', 'deleted_at'];

    public function facebookPage(): BelongsTo
    {
        return $this->belongsTo(config('instagram.models.facebook_page'), 'page_id', 'page_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(config('instagram.models.facebook_post'), 'post_id', 'post_id');
    }
}