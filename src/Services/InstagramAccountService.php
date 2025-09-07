<?php

namespace ScriptDevelop\InstagramApiManager\Services;

use ScriptDevelop\InstagramApiManager\InstagramApi\ApiClient;
use ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount;
use ScriptDevelop\InstagramApiManager\Models\InstagramProfile;
use ScriptDevelop\InstagramApiManager\Models\OauthState;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Carbon;

class InstagramAccountService
{
    protected ApiClient $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient(
            config('instagram.graph_base_url', 'https://graph.instagram.com'),
            config('instagram.api_version', 'v19.0'),
            (int) config('instagram.timeout', 30)
        );
    }

    public function getAuthorizationUrl(array $scopes = [
        'instagram_business_basic',
        'instagram_business_manage_messages',
        'instagram_business_manage_comments',
        'instagram_business_content_publish',
        'instagram_business_manage_insights'
    ], ?string $state = null): string {
        $clientId = config('instagram.client_id');
        $redirectUri = config('instagram.redirect_uri') ?: route('instagram.auth.callback');
        $scope = implode(',', $scopes);
        $state = $state ?? bin2hex(random_bytes(20));

        // Guardar el estado en la base de datos para validación posterior
        OauthState::create([
            'state' => $state,
            'service' => 'instagram',
            'ip_address' => request()->ip(),
            'expires_at' => Carbon::now()->addMinutes(10)
        ]);

        Log::debug('Estado OAuth guardado en base de datos: ' . $state);

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scope,
            'response_type' => 'code',
            'state' => $state,
            'force_reauth' => 'true',
        ]);

        return "https://www.instagram.com/oauth/authorize?" . $params;
    }

    public function handleCallback(string $code, ?string $state = null): ?InstagramBusinessAccount
    {
        // Verificar que el redirect_uri coincida exactamente
        $expectedRedirectUri = config('instagram.redirect_uri') ?: route('instagram.auth.callback');
        $currentUri = request()->fullUrl();
        
        if (strpos($currentUri, $expectedRedirectUri) === false) {
            Log::error('El redirect_uri no coincide con el configurado', [
                'expected' => $expectedRedirectUri,
                'actual' => $currentUri
            ]);
            return null;
        }

        // Validar estado OAuth
        if ($state) {
            $isValidState = OauthState::isValid($state, 'instagram');
            
            if (!$isValidState) {
                Log::error('El estado de OAuth no es válido o ha expirado', [
                    'received' => $state
                ]);
                return null;
            }
            
            // Eliminar el estado usado
            OauthState::where('state', $state)->where('service', 'instagram')->delete();
        } else {
            Log::warning('No se recibió estado OAuth en el callback');
        }

        DB::beginTransaction();

        try {
            // Crear cliente temporal para OAuth de Instagram (sin versión)
            $oauthClient = new ApiClient(
                config('instagram.oauth_base_url', 'https://api.instagram.com'),
                '', // Sin versión para endpoints de OAuth
                (int) config('instagram.timeout', 30)
            );

            // Intercambiar código por token de acceso - USAR form_params (x-www-form-urlencoded)
            $response = $oauthClient->request(
                'POST',
                'oauth/access_token',
                [], // Sin parámetros en la URL
                [ // Datos en el cuerpo como form_params (x-www-form-urlencoded)
                    'form_params' => [
                        'client_id' => config('instagram.client_id'),
                        'client_secret' => config('instagram.client_secret'),
                        'grant_type' => 'authorization_code',
                        'redirect_uri' => config('instagram.redirect_uri') ?: route('instagram.auth.callback'),
                        'code' => $code,
                    ]
                ]
            );

            // Manejar ambos formatos de respuesta de Instagram
            if (isset($response['data'][0]['access_token'])) {
                // Formato antiguo: {"data": [{"access_token": "...", "user_id": "...", "permissions": "..."}]}
                $accessToken = $response['data'][0]['access_token'];
                $userId = $response['data'][0]['user_id'] ?? null;
                $permissions = $response['data'][0]['permissions'] ?? null;
            } elseif (isset($response['access_token'])) {
                // Formato nuevo: {"access_token": "...", "user_id": "...", "permissions": [...]}
                $accessToken = $response['access_token'];
                $userId = $response['user_id'] ?? null;
                
                // Convertir array de permisos a string separado por comas
                $permissions = is_array($response['permissions'] ?? null) 
                    ? implode(',', $response['permissions']) 
                    : ($response['permissions'] ?? null);
            } else {
                Log::error('Instagram OAuth: Formato de respuesta inesperado', ['response' => $response]);
                DB::rollBack();
                return null;
            }

            if (empty($accessToken) || empty($userId)) {
                Log::error('Instagram OAuth: Falta access_token o user_id', ['response' => $response]);
                DB::rollBack();
                return null;
            }

            // Obtener información del perfil usando Graph API
            $profileData = $this->apiClient->request(
                'GET',
                'me',
                [],
                null,
                [
                    'fields' => 'id,username,account_type,media_count,followers_count,follows_count,name,profile_picture_url,biography',
                    'access_token' => $accessToken
                ]
            );

            $account = InstagramBusinessAccount::updateOrCreate(
                ['instagram_business_account_id' => $userId],
                [
                    'access_token' => $accessToken,
                    'tasks' => null,
                    'name' => $profileData['name'] ?? '',
                    'facebook_page_id' => null,
                    'permissions' => $permissions,
                    'token_obtained_at' => now(),
                ]
            );

            if (!empty($profileData)) {
                InstagramProfile::updateOrCreate(
                    ['instagram_business_account_id' => $userId],
                    [
                        'profile_name' => $profileData['name'] ?? '',
                        'username' => $profileData['username'] ?? null,
                        'profile_picture' => $profileData['profile_picture_url'] ?? null,
                        'bio' => $profileData['biography'] ?? null,
                    ]
                );
            }

            DB::commit();
            return $account;

        } catch (Exception $e) {
            Log::error('Error en OAuth Instagram:', ['error' => $e->getMessage()]);
            DB::rollBack();
            return null;
        }
    }

    public function exchangeForLongLivedToken(string $shortLivedToken): ?array
    {
        try {
            // Crear cliente para endpoint de exchange (según documentación)
            $exchangeClient = new ApiClient(
                config('instagram.graph_base_url', 'https://graph.instagram.com'),
                '', // Sin versión para este endpoint
                (int) config('instagram.timeout', 30)
            );

            return $exchangeClient->request(
                'GET',
                'access_token',
                [],
                null,
                [
                    'grant_type' => 'ig_exchange_token',
                    'client_secret' => config('instagram.client_secret'),
                    'access_token' => $shortLivedToken,
                ]
            );

        } catch (Exception $e) {
            Log::error('Error intercambiando token:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function refreshLongLivedToken(string $longLivedToken): ?array
    {
        try {
            // Verificar que el token tenga al menos 24 horas de antigüedad
            $account = InstagramBusinessAccount::where('access_token', $longLivedToken)->first();
            if (!$account || !$account->token_obtained_at) {
                Log::error('No se puede refrescar token: cuenta no encontrada o sin fecha de obtención');
                return null;
            }
            
            $tokenAge = now()->diffInHours($account->token_obtained_at);
            if ($tokenAge < 24) {
                Log::error('No se puede refrescar token: debe tener al menos 24 horas de antigüedad');
                return null;
            }
            
            // Verificar permiso instagram_business_basic
            if (!$this->hasPermission($account, 'instagram_business_basic')) {
                Log::error('No se puede refrescar token: falta permiso instagram_business_basic');
                return null;
            }

            // Crear cliente para endpoint de refresh (según documentación)
            $refreshClient = new ApiClient(
                config('instagram.graph_base_url', 'https://graph.instagram.com'),
                '', // Sin versión para este endpoint
                (int) config('instagram.timeout', 30)
            );

            return $refreshClient->request(
                'GET',
                'refresh_access_token',
                [],
                null,
                [
                    'grant_type' => 'ig_refresh_token',
                    'access_token' => $longLivedToken,
                ]
            );

        } catch (Exception $e) {
            Log::error('Error refrescando token:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Verificar si un token tiene un permiso específico
     */
    public function hasPermission(InstagramBusinessAccount $account, string $permission): bool
    {
        if (empty($account->permissions)) {
            return false;
        }

        $permissions = explode(',', $account->permissions);
        return in_array(trim($permission), $permissions);
    }

    /**
     * Método adicional para vincular una cuenta de Instagram con una página de Facebook
     */
    public function linkWithFacebookPage(string $instagramAccountId, string $facebookPageId): bool
    {
        try {
            $account = InstagramBusinessAccount::find($instagramAccountId);
            if ($account) {
                $account->facebook_page_id = $facebookPageId;
                return $account->save();
            }
            return false;
        } catch (Exception $e) {
            Log::error('Error vinculando cuenta con página de Facebook:', ['error' => $e->getMessage()]);
            return false;
        }
    }
}