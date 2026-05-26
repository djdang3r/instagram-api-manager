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
}
