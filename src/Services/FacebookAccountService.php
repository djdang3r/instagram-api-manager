<?php

namespace ScriptDevelop\InstagramApiManager\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ScriptDevelop\InstagramApiManager\Models\FacebookPage;

class FacebookAccountService
{
    protected string $authBaseUrl = 'https://www.facebook.com/v23.0/dialog/oauth';
    protected string $tokenExchangeUrl = 'https://graph.facebook.com/v23.0/oauth/access_token';

    /**
     * Genera URL de autorización OAuth para Facebook.
     *
     * @param array $scopes Lista de permisos requeridos
     * @param string|null $state Valor opcional para proteger CSRF o seguimiento
     * @return string URL para la autorización de Facebook
     */
    public function getAuthorizationUrl(array $scopes = ['pages_show_list','pages_messaging'], ?string $state = null): string
    {
        $clientId = config('facebook.client_id');
        $redirectUri = route('facebook.auth.callback');

        if (!$state) {
            $state = Str::random(40);
        }

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => implode(',', $scopes),
            'response_type' => 'code',
            'state' => $state,
        ]);

        return $this->authBaseUrl . '?' . $params;
    }

    /**
     * Procesa el callback OAuth de Facebook: intercambia código por token y guarda la cuenta.
     *
     * @param string $code Código recibido en el callback
     * @return FacebookPage|null
     */
    public function handleCallback(string $code): ?FacebookPage
    {
        try {
            $response = Http::get($this->tokenExchangeUrl, [
                'client_id' => config('facebook.client_id'),
                'client_secret' => config('facebook.client_secret'),
                'redirect_uri' => route('facebook.auth.callback'),
                'code' => $code,
            ]);

            if (!$response->successful()) {
                Log::error('Error intercambio token Facebook', ['response' => $response->body()]);
                return null;
            }

            $data = $response->json();

            if (!isset($data['access_token'])) {
                Log::error('Datos incompletos token Facebook', ['response' => $data]);
                return null;
            }

            // Aquí puedes llamar a la API para obtener info de páginas,
            // y guardar o actualizar en base de datos. Como ejemplo, devuelvo null.
            return null;

        } catch (\Throwable $e) {
            Log::error('Excepción en OAuth Facebook', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
