<?php

namespace ScriptDevelop\InstagramApiManager\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ScriptDevelop\InstagramApiManager\Services\FacebookAccountService;
use Illuminate\Support\Facades\Log;

class FacebookAuthController extends Controller
{
    /**
     * Maneja el callback de autenticación OAuth de Facebook
     */
    public function callback(Request $request)
    {
        $code = $request->get('code');
        $error = $request->get('error');
        $errorReason = $request->get('error_reason');
        $errorDescription = $request->get('error_description');

        if ($error) {
            Log::channel('facebook')->error('Error de autorización Facebook:', [
                'error' => $error,
                'reason' => $errorReason,
                'description' => $errorDescription
            ]);

            return redirect('/')->with('error', "Error de autorización: $errorDescription");
        }

        if (!$code) {
            return redirect('/')->with('error', 'No se recibió código de autorización');
        }

        try {
            $facebookAccountService = app(FacebookAccountService::class);
            $result = $facebookAccountService->handleCallback($code);

            if (!$result) {
                return redirect('/')->with('error', 'No se pudieron obtener las páginas de Facebook');
            }

            return redirect('/')->with('success', 'Autenticación completada y páginas obtenidas');

        } catch (\Exception $e) {
            Log::channel('facebook')->error('Excepción en callback Facebook:', ['error' => $e->getMessage()]);
            return redirect('/')->with('error', 'Error interno del servidor');
        }
    }

    /**
     * Redirige al usuario a Facebook para autorizar la aplicación.
     */
    public function connect()
    {
        $facebookAccountService = app(FacebookAccountService::class);
        $authUrl = $facebookAccountService->getAuthorizationUrl();

        return redirect($authUrl);
    }
}