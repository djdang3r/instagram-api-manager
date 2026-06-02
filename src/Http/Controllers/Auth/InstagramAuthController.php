<?php

namespace ScriptDevelop\InstagramApiManager\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ScriptDevelop\InstagramApiManager\Services\InstagramAccountService;
use Illuminate\Support\Facades\Log;

class InstagramAuthController extends Controller
{
    /**
     * Maneja el callback de autenticación OAuth de Instagram
     */
    public function callback(Request $request)
    {
        $custom_redirect_success_url = $this->getValidRedirectUrl('instagram.meta_auth.custom_redirect_success_url');
        $custom_redirect_error_url = $this->getValidRedirectUrl('instagram.meta_auth.custom_redirect_error_url');
        $custom_redirect_warning_url = $this->getValidRedirectUrl('instagram.meta_auth.custom_redirect_warning_url');

        // Primero, verificar si estamos en la redirección intermedia de l.instagram.com
        if ($request->has('u')) {
            $redirectUrl = urldecode($request->input('u'));

            $urlParts = parse_url($redirectUrl);
            if (isset($urlParts['query'])) {
                parse_str($urlParts['query'], $queryParams);

                $code = $queryParams['code'] ?? null;
                $state = $queryParams['state'] ?? null;

                // Limpiar el #_ del código si está presente (según documentación)
                if ($code && strpos($code, '#_') !== false) {
                    $code = str_replace('#_', '', $code);
                    Log::channel('instagram')->debug('Código limpiado de #_', ['code_original' => $queryParams['code'], 'code_limpio' => $code]);
                }

                if ($code) {
                    return redirect()->route('instagram.auth.callback', [
                        'code' => $code,
                        'state' => $state
                    ]);
                }
            }

            Log::channel('instagram')->error('No se pudo extraer el código de la URL intermedia', ['url' => $redirectUrl]);
            return redirect($custom_redirect_error_url)->with('error', 'Error en el proceso de autenticación');
        }

        // Si llegamos aquí, es el callback directo con los parámetros
        $code = $request->get('code');
        $state = $request->get('state');
        $error = $request->get('error');
        $errorReason = $request->get('error_reason');
        $errorDescription = $request->get('error_description');

        // Limpiar el #_ del código si está presente (según documentación)
        if ($code && strpos($code, '#_') !== false) {
            $originalCode = $code;
            $code = str_replace('#_', '', $code);
            Log::channel('instagram')->debug('Código limpiado de #_', ['code_original' => $originalCode, 'code_limpio' => $code]);
        }

        Log::channel('instagram')->debug('Callback de Instagram recibido', [
            'code' => $code,
            'state' => $state,
            'error' => $error,
            'error_reason' => $errorReason,
            'error_description' => $errorDescription
        ]);

        // Manejar cancelación de autorización según documentación
        if ($error === 'access_denied' && $errorReason === 'user_denied') {
            Log::channel('instagram')->warning('Usuario denegó la autorización de Instagram', [
                'error_reason' => $errorReason,
                'error_description' => $errorDescription
            ]);

            return redirect($custom_redirect_warning_url)->with('warning', 'El usuario denegó los permisos solicitados');
        }

        if ($error) {
            Log::channel('instagram')->error('Error de autorización Instagram:', [
                'error' => $error,
                'reason' => $errorReason,
                'description' => $errorDescription
            ]);

            return redirect($custom_redirect_error_url)->with('error', "Error de autorización: $errorDescription");
        }

        if (!$code) {
            Log::channel('instagram')->error('No se recibió código de autorización en el callback');
            return redirect($custom_redirect_error_url)->with('error', 'No se recibió código de autorización');
        }

        try {
            $instagramAccountService = app(InstagramAccountService::class);
            $account = $instagramAccountService->handleCallback($code, $state);

            if (!$account) {
                return redirect($custom_redirect_error_url)->with('error', 'No se pudo procesar la autenticación');
            }

            // Verificar si tenemos el permiso básico necesario para obtener token largo
            if (!$instagramAccountService->hasPermission($account, 'instagram_business_basic')) {
                Log::channel('instagram')->warning('La cuenta no tiene el permiso instagram_business_basic necesario para obtener token largo');
                return redirect($custom_redirect_warning_url)->with('success', 'Autenticación completada. Pero no tiene permisos para token largo.');
            }

            // Opcional: intercambiar por token largo
            $longLivedTokenData = $instagramAccountService->exchangeForLongLivedToken($account->access_token);
            if ($longLivedTokenData && isset($longLivedTokenData['access_token'])) {
                $account->access_token = $longLivedTokenData['access_token'];
                $account->token_expires_in = $longLivedTokenData['expires_in'] ?? null;
                $account->token_obtained_at = now();
                $account->save();

                return redirect($custom_redirect_success_url)->with('success', 'Autenticación completada y token largo obtenido');
            }

            return redirect($custom_redirect_success_url)->with('success', 'Autenticación completada. La cuenta se guardó correctamente.');

        } catch (\Exception $e) {
            Log::channel('instagram')->error('Excepción en callback Instagram:', ['error' => $e->getMessage()]);
            return redirect($custom_redirect_error_url)->with('error', 'Error interno del servidor');
        }
    }

    public function connect()
    {
        $instagramAccountService = app(InstagramAccountService::class);
        $authUrl = $instagramAccountService->getAuthorizationUrl();

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