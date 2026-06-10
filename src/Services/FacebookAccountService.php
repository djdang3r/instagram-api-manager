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

                    if( empty($page['access_token']) ) {
                        Log::channel('facebook')->warning('La página de Facebook no tiene access_token, se omitirá', ['page' => $page]);
                        continue;
                    }

                    $this->subscribeApp($page['id'], $page['access_token']);
                    InstagramModelResolver::facebook_page()->updateOrCreate(
                        ['page_id' => $page['id']],
                        [
                            'meta_app_id'                  => $metaApp->id,
                            'name'                         => $page['name'] ?? '',
                            'access_token'                 => $page['access_token'],
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

    /**
     * Suscribe la app a webhooks de una página de Facebook (Messenger).
     *
     * Documentación: POST /{page-id}/subscribed_apps
     * https://developers.facebook.com/docs/graph-api/reference/page/subscribed_apps/
     *
     * @param string $pageId ID de la página de Facebook
     * @param string $pageAccessToken Token de acceso de la página
     * @param array|null $subscribedFields Campos webhook a suscribir (opcional)
     * @return array | null Respuesta de la API o null si falla
      * @throws \InvalidArgumentException Si faltan parámetros requeridos
     */
    public function subscribeApp(string $pageId, string $pageAccessToken, ?array $subscribedFields = null): array | null
    {
        if ($pageId === '' || $pageAccessToken === '') {
            throw new \InvalidArgumentException('pageId and pageAccessToken are required');
        }

        if ($subscribedFields === null) {
            $subscribedFields = config('facebook.webhook.subscribed_fields', []);
        }

        $subscribedFields = array_values(
            array_filter(
                array_map('trim', $subscribedFields),
                static fn ($field) => $field !== ''
            )
        );

        $query = [
            'access_token' => $pageAccessToken,
        ];

        if (!empty($subscribedFields)) {
            // Graph API requiere subscribed_fields en CSV.
            $query['subscribed_fields'] = implode(',', $subscribedFields);
        }

        Log::channel('facebook')->debug('Suscribiendo app a página de Facebook', [
            'page_id' => $pageId,
            'subscribed_fields' => $subscribedFields,
        ]);

        $response = null;

        try {
            $response = $this->apiClient->request(
                'POST',
                $pageId . '/subscribed_apps',
                [],
                null,
                $query
            );
        } catch (Exception $e) {
            Log::channel('facebook')->error('Error suscribiendo aplicación a Facebook:', ['error' => $e->getMessage()]);
        }

        Log::channel('facebook')->debug('Respuesta de subscribeApp (Facebook):', $response);
        return $response;
    }
}