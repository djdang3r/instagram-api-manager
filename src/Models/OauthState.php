<?php

namespace ScriptDevelop\InstagramApiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class OauthState extends Model
{
    protected $table = 'oauth_states';

    protected $fillable = [
        'state',
        'service',
        'ip_address',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime'
    ];

    /**
     * Verificar si un estado es válido
     */
    public static function isValid(string $state, string $service): bool
    {
        return self::where('state', $state)
            ->where('service', $service)
            ->where('expires_at', '>', Carbon::now())
            ->exists();
    }

    /**
     * Eliminar estados expirados
     */
    public static function cleanupExpired(): int
    {
        return self::where('expires_at', '<=', Carbon::now())->delete();
    }
}