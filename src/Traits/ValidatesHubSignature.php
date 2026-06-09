<?php

namespace ScriptDevelop\InstagramApiManager\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait ValidatesHubSignature
{
    /**
     * Valida la firma X-Hub-Signature-256 que Meta envía en cada POST del webhook.
     * Usa SHA256 con el App Secret como clave.
     */
    protected function validateHubSignature(Request $request, string $platform = 'instagram'): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (!$signature) {
            Log::channel($platform)->warning('⚠️ Falta header X-Hub-Signature-256 en webhook');
            return false;
        }

        $appSecret = config("{$platform}.meta_auth.client_secret");

        if (empty($appSecret)) {
            Log::channel($platform)->error('❌ App Secret no configurado. No se puede validar firma.');
            return false;
        }

        $payload = $request->getContent();
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);

        if (!hash_equals($expected, $signature)) {
            Log::channel($platform)->warning('❌ Firma X-Hub-Signature-256 inválida', [
                'expected' => substr($expected, 0, 20) . '...',
                'received' => substr($signature, 0, 20) . '...',
            ]);
            return false;
        }

        return true;
    }

    /**
     * Detecta si una excepción es por token inválido/expirado (error 190 de Meta).
     */
    protected function isTokenError(\Exception $e): bool
    {
        $msg = $e->getMessage();
        return str_contains($msg, '190')
            || str_contains($msg, 'Error validating access token')
            || str_contains($msg, 'Invalid OAuth');
    }

    /**
     * Intenta refrescar el token de una cuenta/página cuando se detecta error 190.
     */
    protected function tryRefreshToken(string $accountId, string $platform = 'instagram'): void
    {
        try {
            if ($platform === 'instagram') {
                $account = \ScriptDevelop\InstagramApiManager\Support\InstagramModelResolver::instagram_business_account()
                    ->where('instagram_business_account_id', $accountId)
                    ->first();
                if ($account) {
                    app(\ScriptDevelop\InstagramApiManager\Services\InstagramAccountService::class)
                        ->refreshAndStoreLongLivedToken($account);
                }
            } elseif ($platform === 'facebook') {
                $page = \ScriptDevelop\InstagramApiManager\Models\FacebookPage::where('page_id', $accountId)->first();
                if ($page) {
                    app(\ScriptDevelop\InstagramApiManager\Services\FacebookAccountService::class)
                        ->refreshAndStoreLongLivedToken($page);
                }
            }
        } catch (\Exception $e) {
            Log::channel($platform)->warning('No se pudo refrescar token automáticamente', [
                'account_id' => $accountId, 'error' => $e->getMessage(),
            ]);
        }
    }
}
