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
        'username',
        'profile_picture',
    ];

    protected $dates = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    public function facebookPage(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'page_id', 'page_id');
    }
}
