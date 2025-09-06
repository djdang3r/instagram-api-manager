<?php

namespace ScriptDevelop\InstagramApiManager\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ScriptDevelop\InstagramApiManager\Services\InstagramAccountService;

class InstagramAuthController extends Controller
{
    /**
     * Maneja el callback de autenticación OAuth de Instagram,
     * intercambia código por token largo y guarda datos en BD.
     */
    public function callback(Request $request)
    {
        $code = $request->get('code');
        $error = $request->get('error');

        if ($error) {
            return redirect('/')->with('error', 'Error de autorización de Instagram: ' . $error);
        }

        if (!$code) {
            return redirect('/')->with('error', 'No se recibió código de autorización de Instagram.');
        }

        $instagramAccountService = app(InstagramAccountService::class);

        // Intercambiar código por token corto y guardar cuenta
        $account = $instagramAccountService->handleCallback($code);
        if (!$account) {
            return redirect('/')->with('error', 'No se pudo guardar la cuenta Instagram.');
        }

        // Intercambiar token corto por token largo
        $longLivedTokenData = $instagramAccountService->exchangeForLongLivedToken($account->access_token);
        if ($longLivedTokenData && isset($longLivedTokenData['access_token'])) {
            // Actualizar token y duración en la cuenta
            $account->access_token = $longLivedTokenData['access_token'];
            $account->token_expires_in = $longLivedTokenData['expires_in'] ?? null;
            $account->save();
        }

        return redirect('/')->with('success', 'Autenticación con Instagram completada y cuenta almacenada con token largo.');
    }
}
