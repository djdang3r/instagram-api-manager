<?php

namespace ScriptDevelop\InstagramApiManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class InstagramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verificación del webhook (GET)
        if ($request->isMethod('get')) {
            $challenge = $request->get('hub_challenge');
            $verify_token = $request->get('hub_verify_token');
            $expected_token = config('instagram.webhook_verify_token'); // sacado de .env con clave INSTAGRAM_WEBHOOK_VERIFY_TOKEN

            if ($verify_token === $expected_token && $challenge) {
                return response($challenge, 200);
            }
            return response('Forbidden', 403);
        }

        // Manejo de eventos (POST)
        if ($request->isMethod('post')) {
            $data = $request->all();

            // Aquí procesas los eventos según estructura
            // Ejemplo guardarlo, loguearlo, procesar en background, etc.
            // Puedes crear un servicio para manejar los datos

            // Log::channel('instagram')->info('Instagram Webhook event: ', $data);

            return response('EVENT_RECEIVED', 200);
        }

        return response('Method Not Allowed', 405);
    }
}
