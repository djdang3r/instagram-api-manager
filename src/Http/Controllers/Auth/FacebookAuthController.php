<?php

namespace ScriptDevelop\InstagramApiManager\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;

class FacebookAuthController extends Controller
{
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

        $response = Http::asForm()->post('https://graph.facebook.com/v19.0/oauth/access_token', [
            'client_id' => config('facebook.client_id'),
            'client_secret' => config('facebook.client_secret'),
            'redirect_uri' => route('facebook.auth.callback'),
            'code' => $code,
        ]);

        if ($response->failed()) {
            return redirect('/')->with('error', 'No se pudo obtener el token de acceso de Facebook.');
        }

        $data = $response->json();

        // Manejar token de acceso, guardar en sesión, base de datos, etc.
        session(['facebook_access_token' => $data['access_token']]);

        return redirect('/')->with('success', 'Autenticación con Facebook completada.');
    }
}
