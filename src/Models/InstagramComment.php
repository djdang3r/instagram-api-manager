<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class InstagramComment extends Model
{
    use GeneratesUlid;

    protected $table = 'instagram_comments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'comment_id',
        'instagram_business_account_id',
        'instagram_media_id',
        'instagram_user_id',
        'text',
        'username',
        'profile_picture_url',
        'created_time',
        'message_type',
        'parent_comment_id',
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

    public function instagramBusinessAccount(): BelongsTo
    {
        return $this->belongsTo(
            config('instagram.models.instagram_business_account'),
            'instagram_business_account_id',
            'instagram_business_account_id'
        );
    }
}