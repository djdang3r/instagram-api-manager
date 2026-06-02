<?php

namespace ScriptDevelop\InstagramApiManager\Services;

use ScriptDevelop\InstagramApiManager\InstagramApi\ApiClient;
use ScriptDevelop\InstagramApiManager\Support\InstagramModelResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class FacebookAccountService
{
    protected ApiClient $apiClient;

    public function __construct()
    {
        $this->apiClient = app(ApiClient::class)
            ->withBaseUrl(config('facebook.api.base_url'))
            ->withVersion(config('facebook.api.version'));
    }

    public function getAuthorizationUrl(array $scopes = ['pages_show_list', 'pages_read_engagement', 'pages_messaging', 'pages_manage_metadata'], ?string $state = null): string
    {
        $clientId = config('facebook.meta_auth.client_id');
        $redirectUri = config('facebook.meta_auth.redirect_uri') ?: route('facebook.auth.callback');
        $state = $state ?? bin2hex(random_bytes(20));

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => implode(',', $scopes),
            'response_type' => 'code',
            'state' => $state,
        ]);

        return "https://www.facebook.com/".config('facebook.api.version')."/dialog/oauth?" . $params;
    }

    public function handleCallback(string $code): bool
    {
        DB::beginTransaction();

        try {
            // Obtener access token de usuario
            $tokenResponse = $this->apiClient->request(
                'GET',
                'oauth/access_token',
                [],
                null,
                [
                    'client_id' => config('facebook.meta_auth.client_id'),
                    'client_secret' => config('facebook.meta_auth.client_secret'),
                    'redirect_uri' => config('facebook.meta_auth.redirect_uri') ?: route('facebook.auth.callback'),
                    'code' => $code,
                ]
            );

            if (!isset($tokenResponse['access_token'])) {
                Log::channel('facebook')->error('Facebook OAuth: Falta access_token', ['response' => $tokenResponse]);
                DB::rollBack();
                return false;
            }

            $accessToken = $tokenResponse['access_token'];

            if( !$accessToken ) {
                Log::channel('facebook')->error('Facebook OAuth: access_token vacío', ['response' => $tokenResponse]);
                DB::rollBack();
                return false;
            }

            $metaApp = InstagramModelResolver::meta_app()->updateOrCreate(
                ['app_id' => config('facebook.meta_auth.client_id')],
                [
                    'app_secret'       => config('facebook.meta_auth.client_secret'),
                    'verify_token'     => config('facebook.webhook.verify_token'),
                    'app_access_token' => $accessToken,
                    'is_active'        => true,
                ]
            );

            $fields = 'id,name,access_token,tasks,instagram_business_account';

            // Obtener páginas del usuario (incluyendo paginación completa)
            $pagesResponse = $this->apiClient->request(
                'GET',
                'me/accounts',
                [],
                null,
                [
                    'fields' => $fields,
                    'access_token' => $accessToken,
                ]
            );

            $savedPages = 0;
            $iterations = 0;
            $maxIterations = 200;

            while (!empty($pagesResponse['data']) && $iterations < $maxIterations) {
                foreach ($pagesResponse['data'] as $page) {
                    InstagramModelResolver::facebook_page()->updateOrCreate(
                        ['page_id' => $page['id']],
                        [
                            'meta_app_id'                  => $metaApp->id,
                            'name'                         => $page['name'] ?? '',
                            'access_token'                 => $page['access_token'] ?? '',
                            'tasks'                        => $page['tasks'] ?? [],
                            'instagram_business_account'   => $page['instagram_business_account']['id'] ?? null,
                        ]
                    );

                    $savedPages++;
                }

                $nextPageUrl = $pagesResponse['paging']['next'] ?? null;
                $afterCursor = $pagesResponse['paging']['cursors']['after'] ?? null;

                if (!$nextPageUrl && !$afterCursor) {
                    Log::channel('facebook')->info('No hay más páginas de Facebook para paginar');
                    break;
                }

                if ($nextPageUrl) {
                    $pagesResponse = $this->apiClient->request(
                        'GET',
                        $nextPageUrl,
                        [],
                        null,
                        [],
                        [],
                        true
                    );
                } else {
                    $pagesResponse = $this->apiClient->request(
                        'GET',
                        'me/accounts',
                        [],
                        null,
                        [
                            'fields' => $fields,
                            'access_token' => $accessToken,
                            'after' => $afterCursor,
                        ]
                    );
                }

                $iterations++;
            }

            if ($savedPages === 0) {
                Log::channel('facebook')->warning('No se obtuvieron páginas de Facebook');
                DB::rollBack();
                return false;
            }

            if ($iterations >= $maxIterations) {
                Log::channel('facebook')->warning('Se alcanzó el límite de iteraciones al paginar páginas de Facebook', [
                    'max_iterations' => $maxIterations,
                ]);
            }

            DB::commit();
            return true;

        } catch (Exception $e) {
            Log::channel('facebook')->error('Error en OAuth Facebook:', ['error' => $e->getMessage()]);
            DB::rollBack();
            return false;
        }
    }

    /**
     * Refrescar token de larga duración de una página de Facebook.
     */
    public function refreshLongLivedToken(Model $page): ?array
    {
        try {
            $token = $page->access_token;
            if (!$token) {
                Log::channel('facebook')->error('No se puede refrescar token: la página no tiene access_token');
                return null;
            }

            $response = $this->apiClient->request(
                'GET',
                'oauth/access_token',
                [],
                null,
                [
                    'grant_type' => 'fb_exchange_token',
                    'client_id' => config('facebook.meta_auth.client_id'),
                    'client_secret' => config('facebook.meta_auth.client_secret'),
                    'fb_exchange_token' => $token,
                ]
            );

            return $response['access_token'] ?? null ? $response : null;
        } catch (Exception $e) {
            Log::channel('facebook')->error('Error refrescando token de Facebook:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function refreshAndStoreLongLivedToken(Model $page): bool
    {
        $response = $this->refreshLongLivedToken($page);
        if (!$response || empty($response['access_token'])) {
            return false;
        }

        $page->access_token = $response['access_token'];
        $page->token_expires_in = $response['expires_in'] ?? null;
        $page->token_obtained_at = now();
        return $page->save();
    }
}