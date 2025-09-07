<?php

namespace ScriptDevelop\InstagramApiManager\Services;

use ScriptDevelop\InstagramApiManager\InstagramApi\ApiClient;
use ScriptDevelop\InstagramApiManager\InstagramApi\Endpoints\InstagramEndpoints;
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
        $this->apiClient = new ApiClient(
            config('instagram.api_base_url'),
            config('instagram.api_version'),
            (int) config('instagram.timeout', 30)
        );
    }

    public function getAuthorizationUrl(array $scopes = [
        'instagram_business_basic',
        'instagram_business_manage_messages',
        'instagram_business_manage_comments',
        'instagram_business_content_publish',
        'instagram_business_manage_insights',
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

        $url = "https://www.instagram.com/oauth/authorize?" . $params;

        if (app()->runningInConsole()) {
            file_put_contents('php://stdout', "URL OAuth Instagram: $url\n");
        } else {
            Log::info("URL OAuth Instagram generada: $url");
        }

        return $url;
    }

    public function handleCallback(string $code): ?InstagramBusinessAccount
    {
        DB::beginTransaction();

        try {
            $response = $this->apiClient->request(
                'POST',
                InstagramEndpoints::OAUTH_ACCESS_TOKEN,
                [],
                [
                    'client_id' => config('instagram.client_id'),
                    'client_secret' => config('instagram.client_secret'),
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => config('instagram.redirect_uri') ?: route('instagram.auth.callback'),
                    'code' => $code,
                ]
            );

            if (!isset($response['access_token']) || !isset($response['user_id'])) {
                Log::error('Respuesta de API incompleta', ['response' => $response]);
                DB::rollBack();
                return null;
            }

            $accessToken = $response['access_token'];
            $userId = $response['user_id'];

            // Usar el endpoint para obtener perfil con build
            $profileUrl = InstagramEndpoints::build(InstagramEndpoints::GET_USER_PROFILE_INFO, ['ig_scoped_id' => $userId]);
            $profileData = $this->apiClient->request(
                'GET',
                $profileUrl,
                [],
                null,
                ['access_token' => $accessToken]
            );

            $account = InstagramBusinessAccount::updateOrCreate(
                ['instagram_business_account_id' => $userId],
                [
                    'access_token' => $accessToken,
                    'tasks' => null,
                    'name' => $profileData['name'] ?? '',
                    'facebook_page_id' => '',
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
            Log::error('Error en OAuth Instagram AccountService', ['error' => $e->getMessage()]);
            DB::rollBack();
            return null;
        }
    }

    public function exchangeForLongLivedToken(string $shortLivedToken): ?array
    {
        try {
            return $this->apiClient->request(
                'GET',
                InstagramEndpoints::OAUTH_ACCESS_TOKEN,
                [],
                null,
                [
                    'grant_type' => 'ig_exchange_token',
                    'client_secret' => config('instagram.client_secret'),
                    'access_token' => $shortLivedToken,
                ]
            );

        } catch (Exception $e) {
            Log::error('Error intercambiando token corto por largo Instagram', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function refreshLongLivedToken(string $longLivedToken): ?array
    {
        try {
            return $this->apiClient->request(
                'GET',
                InstagramEndpoints::REFRESH_ACCESS_TOKEN,
                [],
                null,
                [
                    'grant_type' => 'ig_refresh_token',
                    'access_token' => $longLivedToken,
                ]
            );

        } catch (Exception $e) {
            Log::error('Error refrescando token largo Instagram', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
