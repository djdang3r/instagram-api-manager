<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class FacebookPageStats extends Model
{
    use GeneratesUlid;

    protected $table = 'facebook_page_stats';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'page_id',
        'date',
        'page_followers',
        'page_likes',
        'total_ads_reach',
        'total_page_views',
        'total_page_impressions',
        'messages_sent',
        'messages_received',
        'raw_data',
    ];

    protected $casts = [
        'page_followers' => 'integer',
        'page_likes' => 'integer',
        'total_ads_reach' => 'integer',
        'total_page_views' => 'integer',
        'total_page_impressions' => 'integer',
        'messages_sent' => 'integer',
        'messages_received' => 'integer',
        'date' => 'date',
        'raw_data' => 'array',
    ];

    protected $dates = ['created_at', 'updated_at', 'date'];

    public function facebookPage(): BelongsTo
    {
        return $this->belongsTo(config('instagram.models.facebook_page'), 'page_id', 'page_id');
    }
}