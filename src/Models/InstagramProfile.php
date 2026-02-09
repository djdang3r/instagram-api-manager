<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;

class InstagramProfile extends Model
{
    use SoftDeletes, GeneratesUlid;

    protected $table = 'instagram_profiles';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'instagram_business_account_id',
        'instagram_scoped_id', // ← Instagram-Scoped ID (usado en webhooks)

        // Información básica
        'profile_name',
        'user_id',
        'username',
        'profile_picture',
        'bio',

        // Nuevos campos de la API
        'account_type',
        'followers_count',
        'follows_count',
        'media_count',

        // Campos adicionales
        'website',
        'category_name',
        'is_verified',

        // Metadata
        'last_synced_at',
        'raw_api_response',
    ];

    protected $casts = [
        'followers_count' => 'integer',
        'follows_count' => 'integer',
        'media_count' => 'integer',
        'is_verified' => 'boolean',
        'last_synced_at' => 'datetime',
        'raw_api_response' => 'array',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'last_synced_at'
    ];

    /**
     * Relación con la cuenta de negocio de Instagram
     */
    public function instagramBusinessAccount(): BelongsTo
    {
        return $this->belongsTo(
            InstagramBusinessAccount::class,
            'instagram_business_account_id',
            'instagram_business_account_id'
        );
    }

    /**
     * Scope para filtrar por tipo de cuenta
     */
    public function scopeAccountType($query, $type)
    {
        return $query->where('account_type', $type);
    }

    /**
     * Scope para cuentas verificadas
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope para cuentas con muchos seguidores
     */
    public function scopePopular($query, $minFollowers = 10000)
    {
        return $query->where('followers_count', '>=', $minFollowers);
    }

    /**
     * Verificar si la cuenta es de negocio
     */
    public function isBusinessAccount(): bool
    {
        return $this->account_type === 'Business';
    }

    /**
     * Verificar si la cuenta es de creador de medios
     */
    public function isMediaCreatorAccount(): bool
    {
        return $this->account_type === 'Media_Creator';
    }

    /**
     * Obtener la URL del perfil de Instagram
     */
    public function getProfileUrlAttribute(): string
    {
        return "https://www.instagram.com/{$this->username}/";
    }

    /**
     * Actualizar la fecha de última sincronización
     */
    public function markAsSynced(): bool
    {
        return $this->update(['last_synced_at' => now()]);
    }
}