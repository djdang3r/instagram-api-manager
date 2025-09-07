<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use ScriptDevelop\InstagramApiManager\Traits\GeneratesUlid;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InstagramBusinessAccount extends Model
{
    use SoftDeletes, GeneratesUlid;

    protected $table = 'instagram_business_accounts';

    protected $primaryKey = 'instagram_business_account_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'instagram_business_account_id',
        'facebook_page_id',
        'name',
        'access_token',
        'token_expires_in',
        'permissions',
        'tasks',
        'token_obtained_at', // Nuevo campo para rastrear cuándo se obtuvo el token
    ];

    protected $casts = [
        'tasks' => 'array',
        'token_expires_in' => 'integer',
        'token_obtained_at' => 'datetime',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'token_obtained_at'];

    public function setAccessTokenAttribute($value)
    {
        try {
            $this->attributes['access_token'] = $value ? encrypt($value) : null;
            
            // Actualizar la fecha de obtención del token cuando se establece
            if ($value && empty($this->token_obtained_at)) {
                $this->attributes['token_obtained_at'] = now();
            }
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

    public function profile(): HasOne
    {
        return $this->hasOne(InstagramProfile::class, 'instagram_business_account_id', 'instagram_business_account_id');
    }

    // Relación opcional con Facebook Page
    public function facebookPage(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'facebook_page_id', 'page_id')
                    ->withDefault();
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

    /**
     * Verificar si tiene un permiso específico
     */
    public function hasPermission(string $permission): bool
    {
        if (empty($this->permissions)) {
            return false;
        }

        $permissions = explode(',', $this->permissions);
        return in_array(trim($permission), $permissions);
    }

    /**
     * Verificar si el token de acceso es válido (no expirado)
     */
    public function isTokenValid(): bool
    {
        if (empty($this->access_token)) {
            return false;
        }

        // Si no tenemos información de expiración, asumimos que el token es válido
        // pero podría no serlo (token de corta duración sin información de expiración)
        if (empty($this->token_expires_in) || empty($this->token_obtained_at)) {
            return true;
        }

        try {
            // Calcular la fecha de expiración del token
            $expirationDate = Carbon::parse($this->token_obtained_at)->addSeconds($this->token_expires_in);
            
            // Verificar si el token ha expirado
            if (now()->greaterThanOrEqualTo($expirationDate)) {
                Log::warning('Token de Instagram ha expirado', [
                    'account_id' => $this->instagram_business_account_id,
                    'obtained_at' => $this->token_obtained_at,
                    'expires_in' => $this->token_expires_in,
                    'calculated_expiration' => $expirationDate
                ]);
                return false;
            }
            
            // Verificar si el token está próximo a expirar (menos de 7 días)
            if (now()->addDays(7)->greaterThanOrEqualTo($expirationDate)) {
                Log::info('Token de Instagram próximo a expirar', [
                    'account_id' => $this->instagram_business_account_id,
                    'expires_in' => $this->token_expires_in,
                    'expiration_date' => $expirationDate
                ]);
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error verificando validez del token', [
                'error' => $e->getMessage(),
                'account_id' => $this->instagram_business_account_id
            ]);
            return false;
        }
    }

    /**
     * Verificar si el token está próximo a expirar (menos de 7 días)
     */
    public function isTokenExpiringSoon(): bool
    {
        if (empty($this->access_token) || empty($this->token_expires_in) || empty($this->token_obtained_at)) {
            return false;
        }

        try {
            $expirationDate = Carbon::parse($this->token_obtained_at)->addSeconds($this->token_expires_in);
            return now()->addDays(7)->greaterThanOrEqualTo($expirationDate);
        } catch (\Exception $e) {
            Log::error('Error verificando expiración próxima del token', [
                'error' => $e->getMessage(),
                'account_id' => $this->instagram_business_account_id
            ]);
            return false;
        }
    }

    /**
     * Obtener la fecha de expiración del token
     */
    public function getTokenExpirationDate(): ?Carbon
    {
        if (empty($this->token_expires_in) || empty($this->token_obtained_at)) {
            return null;
        }

        try {
            return Carbon::parse($this->token_obtained_at)->addSeconds($this->token_expires_in);
        } catch (\Exception $e) {
            Log::error('Error calculando fecha de expiración del token', [
                'error' => $e->getMessage(),
                'account_id' => $this->instagram_business_account_id
            ]);
            return null;
        }
    }

    /**
     * Obtener los días restantes hasta la expiración del token
     */
    public function getDaysUntilExpiration(): ?int
    {
        $expirationDate = $this->getTokenExpirationDate();
        
        if (!$expirationDate) {
            return null;
        }

        return now()->diffInDays($expirationDate, false); // negativo si ya expiró
    }
}