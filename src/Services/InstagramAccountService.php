<?php

namespace ScriptDevelop\InstagramApiManager\Services;

use ScriptDevelop\InstagramApiManager\InstagramApi\ApiClient;
use ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount;
use ScriptDevelop\InstagramApiManager\Models\InstagramProfile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class InstagramAccountService
{
    protected ApiClient $apiClient;

    public function __construct()
    {
        // Cliente para Graph API (para la mayoría de endpoints)
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

    public function handleCallback(string $code): ?InstagramBusinessAccount
    {
        DB::beginTransaction();

        try {
            // Crear cliente temporal para OAuth de Instagram (sin versión)
            $oauthClient = new ApiClient(
                config('instagram.oauth_base_url', 'https://api.instagram.com'),
                '', // Sin versión para endpoints de OAuth
                (int) config('instagram.timeout', 30)
            );

            // Intercambiar código por token de acceso - FORMA CORRECTA SEGÚN DOCUMENTACIÓN
            // Usar form-data (multipart) como indica la documentación
            $response = $oauthClient->request(
                'POST',
                'oauth/access_token',
                [], // Sin parámetros en la URL
                [ // Datos en el cuerpo como form-data
                    'multipart' => [
                        [
                            'name' => 'client_id',
                            'contents' => config('instagram.client_id')
                        ],
                        [
                            'name' => 'client_secret',
                            'contents' => config('instagram.client_secret')
                        ],
                        [
                            'name' => 'grant_type',
                            'contents' => 'authorization_code'
                        ],
                        [
                            'name' => 'redirect_uri',
                            'contents' => config('instagram.redirect_uri') ?: route('instagram.auth.callback')
                        ],
                        [
                            'name' => 'code',
                            'contents' => $code
                        ]
                    ]
                ]
            );

            // La respuesta de Instagram viene en formato diferente según la documentación
            if (isset($response['data'][0]['access_token'])) {
                // Formato nuevo según documentación
                $accessToken = $response['data'][0]['access_token'];
                $userId = $response['data'][0]['user_id'] ?? null;
            } elseif (isset($response['access_token'])) {
                // Formato alternativo que podría devolver la API
                $accessToken = $response['access_token'];
                $userId = $response['user_id'] ?? null;
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
                    'name' => $profileData['name'] ?? $userInfo['name'] ?? '',
                    'facebook_page_id' => null,
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
            // Crear cliente para endpoint de exchange
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
            // Crear cliente para endpoint de refresh
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
}