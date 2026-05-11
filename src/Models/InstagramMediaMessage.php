<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class InstagramMediaMessage extends Model
{
    use SoftDeletes, GeneratesUlid;

    protected $table = 'instagram_media_messages';

    protected $primaryKey = 'media_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'media_id',
        'message_id',
        'media_type',
        'url',
        'json',
    ];

    protected $casts = [
        'json' => 'array',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(InstagramMessage::class, 'message_id', 'message_id');
    }
}
