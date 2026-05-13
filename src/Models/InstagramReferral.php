<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramReferral extends Model
{
    protected $table = 'instagram_referrals';

    protected $fillable = [
        'conversation_id',
        'instagram_user_id',
        'instagram_business_account_id',
        'ref_parameter',
        'source',
        'type',
        'processed_at'
    ];

    protected $casts = [
        'processed_at' => 'datetime'
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(config('instagram.models.instagram_conversation'), 'conversation_id');
    }

    public function businessAccount(): BelongsTo
    {
        return $this->belongsTo(config('instagram.models.instagram_business_account'), 'instagram_business_account_id', 'instagram_business_account_id');
    }
}