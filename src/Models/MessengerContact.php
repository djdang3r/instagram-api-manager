<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class MessengerContact extends Model
{
    use SoftDeletes, GeneratesUlid;

    protected $table = 'messenger_contacts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'page_id',
        'messenger_user_id',
        'name',
        'username',
        'profile_picture',
        'last_interaction_at',
        'profile_synced_at',
    ];

    protected $dates = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    public function facebookPage(): BelongsTo
    {
        return $this->belongsTo(config('instagram.models.facebook_page'), 'page_id', 'page_id');
    }
}
