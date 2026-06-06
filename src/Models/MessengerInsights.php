<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class MessengerInsights extends Model
{
    use GeneratesUlid;

    protected $table = 'messenger_insights';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'page_id',
        'date',
        'total_conversations',
        'total_messages_sent',
        'total_messages_received',
        'total_blocked_contacts',
        'total_reported_contacts',
        'page_views',
        'page_impressions',
        'raw_data',
    ];

    protected $casts = [
        'total_conversations' => 'integer',
        'total_messages_sent' => 'integer',
        'total_messages_received' => 'integer',
        'total_blocked_contacts' => 'integer',
        'total_reported_contacts' => 'integer',
        'page_views' => 'integer',
        'page_impressions' => 'integer',
        'date' => 'date',
        'raw_data' => 'array',
    ];

    protected $dates = ['created_at', 'updated_at', 'date'];

    public function facebookPage(): BelongsTo
    {
        return $this->belongsTo(config('instagram.models.facebook_page'), 'page_id', 'page_id');
    }
}