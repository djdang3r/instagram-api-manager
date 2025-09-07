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
        // Primero, verificar si estamos en la redirección intermedia de l.instagram.com
        if ($request->has('u')) {
            // Esta es la redirección intermedia, extraer la URL real
            $redirectUrl = urldecode($request->input('u'));
            
            // Parsear la URL para obtener los parámetros
            $urlParts = parse_url($redirectUrl);
            if (isset($urlParts['query'])) {
                parse_str($urlParts['query'], $queryParams);
                
                // Recuperar el código y estado de la URL
                $code = $queryParams['code'] ?? null;
                $state = $queryParams['state'] ?? null;
                
                if ($code) {
                    // Redirigir a nosotros mismos con los parámetros correctos
                    return redirect()->route('instagram.auth.callback', [
                        'code' => $code,
                        'state' => $state
                    ]);
                }
            }
            
            Log::error('No se pudo extraer el código de la URL intermedia', ['url' => $redirectUrl]);
            return redirect('/')->with('error', 'Error en el proceso de autenticación');
        }

        // Si llegamos aquí, es el callback directo con los parámetros
        $code = $request->get('code');
        $error = $request->get('error');
        $errorReason = $request->get('error_reason');
        $errorDescription = $request->get('error_description');

        if ($error) {
            Log::error('Error de autorización Instagram:', [
                'error' => $error,
                'reason' => $errorReason,
                'description' => $errorDescription
            ]);
            
            return redirect('/')->with('error', "Error de autorización: $errorDescription");
        }

        if (!$code) {
            Log::error('No se recibió código de autorización en el callback');
            return redirect('/')->with('error', 'No se recibió código de autorización');
        }

        try {
            $instagramAccountService = app(InstagramAccountService::class);
            $account = $instagramAccountService->handleCallback($code);
            
            if (!$account) {
                return redirect('/')->with('error', 'No se pudo procesar la autenticación');
            }

            // Opcional: intercambiar por token largo
            $longLivedTokenData = $instagramAccountService->exchangeForLongLivedToken($account->access_token);
            if ($longLivedTokenData && isset($longLivedTokenData['access_token'])) {
                $account->access_token = $longLivedTokenData['access_token'];
                $account->token_expires_in = $longLivedTokenData['expires_in'] ?? null;
                $account->save();
                
                return redirect('/')->with('success', 'Autenticación completada y token largo obtenido');
            }

            return redirect('/')->with('success', 'Autenticación completada. La cuenta se guardó correctamente.');

        } catch (\Exception $e) {
            Log::error('Excepción en callback Instagram:', ['error' => $e->getMessage()]);
            return redirect('/')->with('error', 'Error interno del servidor');
        }
    }
}