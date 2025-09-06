<?php

namespace ScriptDevelop\InstagramApiManager\Services;

use ScriptDevelop\InstagramApiManager\FacebookApi\Endpoints\FacebookEndpoints;
use ScriptDevelop\InstagramApiManager\InstagramApi\ApiClient;
use ScriptDevelop\InstagramApiManager\Models\FacebookPage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class FacebookAccountService
{
    protected ApiClient $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient(
            config('facebook.api_base_url'),
            config('facebook.api_version'),
            (int) config('facebook.timeout', 30)
        );
    }

    public function getAuthorizationUrl(array $scopes = ['pages_show_list', 'pages_messaging'], ?string $state = null): string
    {
        $clientId = config('facebook.client_id');
        $redirectUri = config('facebook.redirect_uri') ?: route('facebook.auth.callback');
        $state = $state ?? bin2hex(random_bytes(20));

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => implode(',', $scopes),
            'response_type' => 'code',
            'state' => $state,
        ]);

        return "https://www.facebook.com/v23.0/dialog/oauth?" . $params;
    }

    public function handleCallback(string $code): bool
    {
        DB::beginTransaction();

        try {
            $response = $this->apiClient->request(
                'GET',
                FacebookEndpoints::GET_USER_MANAGED_PAGES,
                [],
                null,
                [
                    'client_id' => config('facebook.client_id'),
                    'client_secret' => config('facebook.client_secret'),
                    'redirect_uri' => config('facebook.redirect_uri') ?: route('facebook.auth.callback'),
                    'code' => $code,
                ]
            );

            if (!isset($response['access_token'])) {
                Log::error('Datos incompletos token Facebook', ['response' => $response]);
                DB::rollBack();
                return false;
            }

            $accessToken = $response['access_token'];

            $pagesResponse = $this->apiClient->request(
                'GET',
                FacebookEndpoints::GET_USER_MANAGED_PAGES,
                [],
                null,
                [
                    'access_token' => $accessToken,
                ]
            );

            if (empty($pagesResponse['data'])) {
                Log::warning('No se obtuvieron páginas de Facebook con token.');
                DB::rollBack();
                return false;
            }

            foreach ($pagesResponse['data'] as $page) {
                FacebookPage::updateOrCreate(
                    ['page_id' => $page['id']],
                    [
                        'name' => $page['name'] ?? '',
                        'access_token' => $page['access_token'] ?? '',
                        'tasks' => $page['tasks'] ?? [],
                    ]
                );
            }

            DB::commit();

            return true;

        } catch (Exception $e) {
            Log::error('Excepción en OAuth Facebook AccountService', ['error' => $e->getMessage()]);
            DB::rollBack();
            return false;
        }
    }
}
