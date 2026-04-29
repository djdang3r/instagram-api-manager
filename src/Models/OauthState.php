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
     * Scope para verificar si un estado es válido
     */
    public function scopeIsValid($query, string $state, string $service)
    {
        return $query->where('state', $state)
            ->where('service', $service)
            ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Eliminar estados expirados
     */
    public static function cleanupExpired(): int
    {
        return self::where('expires_at', '<=', Carbon::now())->delete();
    }
}