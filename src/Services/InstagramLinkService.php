<?php

namespace ScriptDevelop\InstagramApiManager\Services;

use ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount;
use ScriptDevelop\InstagramApiManager\Models\InstagramReferral;
use Illuminate\Support\Facades\Log;
use Exception;

class InstagramLinkService
{
    /**
     * Generar un enlace ig.me para una cuenta de negocio
     */
    public function generateIgMeLink(InstagramBusinessAccount $account, string $ref = null): string
    {
        return $account->getIgMeLink($ref);
    }

    /**
     * Generar un código QR para un enlace ig.me
     */
    public function generateIgMeQrCode(InstagramBusinessAccount $account, string $ref = null, int $size = 300): string
    {
        $url = $this->generateIgMeLink($account, $ref);
        $encodedUrl = urlencode($url);
        
        // Usar el servicio de Google Charts API para generar QR
        return "https://chart.googleapis.com/chart?cht=qr&chs={$size}x{$size}&chl={$encodedUrl}";
    }

    /**
     * Validar un parámetro ref
     */
    public function validateRefParameter(string $ref): bool
    {
        return preg_match('/^[a-zA-Z0-9_=\-]{1,2083}$/', $ref);
    }

    /**
     * Obtener estadísticas de referrals por parámetro ref
     */
    public function getReferralStats(string $instagramBusinessAccountId, string $ref = null): array
    {
        $query = InstagramReferral::where('instagram_business_account_id', $instagramBusinessAccountId);
        
        if ($ref) {
            $query->where('ref_parameter', $ref);
        }
        
        return [
            'total' => $query->count(),
            'last_7_days' => $query->where('created_at', '>=', now()->subDays(7))->count(),
            'last_30_days' => $query->where('created_at', '>=', now()->subDays(30))->count(),
            'by_source' => $query->groupBy('source')->selectRaw('source, count(*) as count')->get()->toArray(),
            'by_type' => $query->groupBy('type')->selectRaw('type, count(*) as count')->get()->toArray(),
        ];
    }
}