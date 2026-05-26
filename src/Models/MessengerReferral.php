<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class MessengerReferral extends Model
{
    use SoftDeletes, GeneratesUlid;

    protected $table = 'messenger_referrals';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'conversation_id', 'messenger_user_id', 'page_id',
        'ref_parameter', 'source', 'type', 'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(config('instagram.models.messenger_conversation'), 'conversation_id');
    }

    public function facebookPage(): BelongsTo
    {
        return $this->belongsTo(config('instagram.models.facebook_page'), 'page_id', 'page_id');
    }
}
