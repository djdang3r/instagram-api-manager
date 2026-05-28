<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class MessengerMediaMessage extends Model
{
    use SoftDeletes, GeneratesUlid;

    protected $table = 'messenger_media_messages';

    protected $primaryKey = 'media_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'media_id',
        'message_id',
        'media_type',
        'media_url',
        'media_url_hash',
        'local_path',
        'json',
    ];

    protected $casts = [
        'json' => 'array',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(config('instagram.models.messenger_message'), 'message_id', 'message_id');
    }
}
