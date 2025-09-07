<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;
use Illuminate\Support\Facades\Log;

class InstagramBusinessAccount extends Model
{
    use SoftDeletes, GeneratesUlid;

    protected $table = 'instagram_business_accounts';

    protected $primaryKey = 'instagram_business_account_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'instagram_business_account_id',
        'facebook_page_id', // Puede ser null
        'name',
        'access_token',
        'token_expires_in',
        'tasks',
    ];

    protected $casts = [
        'tasks' => 'array',
        'token_expires_in' => 'integer',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function setAccessTokenAttribute($value)
    {
        try {
            $this->attributes['access_token'] = $value ? encrypt($value) : null;
        } catch (\Exception $e) {
            Log::error('Error encrypting access token', ['error' => $e->getMessage()]);
            $this->attributes['access_token'] = null;
        }
    }

    public function getAccessTokenAttribute($value)
    {
        try {
            return $value ? decrypt($value) : null;
        } catch (\Exception $e) {
            Log::error('Error decrypting access token', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Relación opcional con Facebook Page
    public function facebookPage(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'facebook_page_id', 'page_id')
                    ->withDefault(); // Permite valores nulos
    }

    public function instagramProfile(): HasOne
    {
        return $this->hasOne(InstagramProfile::class, 'instagram_business_account_id', 'instagram_business_account_id');
    }

    public function instagramConversations(): HasMany
    {
        return $this->hasMany(InstagramConversation::class, 'instagram_business_account_id', 'instagram_business_account_id');
    }

    public function instagramContacts(): HasMany
    {
        return $this->hasMany(InstagramContact::class, 'instagram_business_account_id', 'instagram_business_account_id');
    }

    /**
     * Método para verificar si la cuenta está vinculada a una página de Facebook
     */
    public function isLinkedToFacebook(): bool
    {
        return !empty($this->facebook_page_id);
    }
}