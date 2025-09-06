<?php

namespace ScriptDevelop\InstagramApiManager\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ScriptDevelop\InstagramApiManager\Services\FacebookAccountService;

class FacebookAuthController extends Controller
{
    /**
     * Maneja el callback de autenticación OAuth de Facebook,
     * intercambia código por token y guarda las páginas vinculadas.
     */
    public function callback(Request $request)
    {
        $code = $request->get('code');
        $error = $request->get('error');

        if ($error) {
            return redirect('/')->with('error', 'Error de autorización de Facebook: ' . $error);
        }

        if (!$code) {
            return redirect('/')->with('error', 'No se recibió código de autorización de Facebook.');
        }

        $facebookAccountService = app(FacebookAccountService::class);
        $result = $facebookAccountService->handleCallback($code);

        if (!$result) {
            return redirect('/')->with('error', 'No se pudo guardar la cuenta Facebook.');
        }

        return redirect('/')->with('success', 'Autenticación con Facebook completada y páginas almacenadas.');
    }
}
