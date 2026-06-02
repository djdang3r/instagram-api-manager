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
        $custom_redirect_success_url = $this->getValidRedirectUrl('facebook.meta_auth.custom_redirect_success_url');
        $custom_redirect_error_url = $this->getValidRedirectUrl('facebook.meta_auth.custom_redirect_error_url');
        $custom_redirect_warning_url = $this->getValidRedirectUrl('facebook.meta_auth.custom_redirect_warning_url');

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

            return redirect($custom_redirect_error_url)->with('error', "Error de autorización: $errorDescription");
        }

        if (!$code) {
            return redirect($custom_redirect_error_url)->with('error', 'No se recibió código de autorización');
        }

        try {
            $facebookAccountService = app(FacebookAccountService::class);
            $result = $facebookAccountService->handleCallback($code);

            if (!$result) {
                return redirect($custom_redirect_error_url)->with('error', 'No se pudieron obtener las páginas de Facebook');
            }

            return redirect($custom_redirect_success_url)->with('success', 'Autenticación completada y páginas obtenidas');

        } catch (\Exception $e) {
            Log::channel('facebook')->error('Excepción en callback Facebook:', ['error' => $e->getMessage()]);
            return redirect($custom_redirect_error_url)->with('error', 'Error interno del servidor');
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

    /**
     * Obtiene una URL de configuración y valida que sea una URL absoluta válida.
     * Si no lo es, retorna url('/').
     */
    private function getValidRedirectUrl(string $configKey): string
    {
        $defaultUrl = url('/');
        $configuredUrl = config($configKey, $defaultUrl);

        if (!is_string($configuredUrl)) {
            return $defaultUrl;
        }

        $configuredUrl = trim($configuredUrl);

        if (filter_var($configuredUrl, FILTER_VALIDATE_URL) === false) {
            return $defaultUrl;
        }

        return $configuredUrl;
    }
}