<?php

namespace ScriptDevelop\InstagramApiManager\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;

class InstagramAuthController extends Controller
{
    /**
     * Maneja el callback de autenticación OAuth de Instagram.
     */
    public function callback(Request $request)
    {
        $code = $request->get('code');
        $error = $request->get('error');

        if ($error) {
            // El usuario rechazó o hubo un error en la autorización
            return redirect('/')->with('error', 'Error de autorización de Instagram: ' . $error);
        }

        if (!$code) {
            return redirect('/')->with('error', 'No se recibió código de autorización de Instagram.');
        }

        // Intercambiar el código por token de acceso
        $response = Http::asForm()->post('https://api.instagram.com/oauth/access_token', [
            'client_id' => config('instagram.client_id'),
            'client_secret' => config('instagram.client_secret'),
            'grant_type' => 'authorization_code',
            'redirect_uri' => route('instagram.auth.callback'),
            'code' => $code,
        ]);

        if ($response->failed()) {
            return redirect('/')->with('error', 'No se pudo obtener el token de acceso desde Instagram.');
        }

        $data = $response->json();

        // Aquí maneja el token: guardar en base de datos, sesión, emitir eventos, etc.
        // Ejemplo: guardar token en sesión temporalmente (ajusta según necesidades)
        session(['instagram_access_token' => $data['access_token']]);

        return redirect('/')->with('success', 'Autenticación con Instagram completada exitosamente.');
    }
}
