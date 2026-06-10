<?php

namespace ScriptDevelop\InstagramApiManager\Services;

use ScriptDevelop\InstagramApiManager\InstagramApi\ApiClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Carbon;
use ScriptDevelop\InstagramApiManager\Support\InstagramModelResolver;

class InstagramAccountService
{
    protected ApiClient $apiClient;
    protected ?Model $currentAccount = null;

    public function __construct()
    {
        $this->apiClient = app(ApiClient::class)
            ->withBaseUrl(config('instagram.api.graph_base_url', 'https://graph.instagram.com'))
            ->withVersion(config('instagram.api.version'));
    }

    /**
     * Establecer la cuenta actual para las operaciones
     */
    public function forAccount(Model $account): self
    {
        $this->currentAccount = $account;
        return $this;
    }

    /**
     * Establecer la cuenta actual por ID
     */
    public function forAccountId(string $accountId): self
    {
        $account = InstagramModelResolver::instagram_business_account()->find($accountId);
        if ($account) {
            $this->currentAccount = $account;
        }
        return $this;
    }

    /**
     * Obtener información del perfil
     */
    public function getProfileInfo(?string $accessToken = null): ?array
    {
        $accessToken = $accessToken ?? $this->currentAccount?->access_token;

        if (!$accessToken) {
            throw new \Exception('Access token is required');
        }

        try {
            return $this->apiClient->request(
                'GET',
                'me',
                [],
                null,
                [
                    'fields' => 'id,username,account_type,media_count,followers_count,follows_count,name,profile_picture_url,biography,website',
                    'access_token' => $accessToken
                ]
            );
        } catch (Exception $e) {
            Log::channel('instagram')->error('Error obteniendo información del perfil:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Obtener medios del usuario
     */
    public function getUserMedia(?string $userId = null, ?string $accessToken = null, int $limit = 100): ?array
    {
        $userId = $userId ?? $this->currentAccount?->instagram_business_account_id;
        $accessToken = $accessToken ?? $this->currentAccount?->access_token;

        if (!$userId || !$accessToken) {
            throw new \Exception('User ID and access token are required');
        }

        try {
            $allMedia = [];
            $after = null;

            do {
                $query = [
                    'access_token' => $accessToken,
                    'fields' => 'id,caption,media_type,media_url,thumbnail_url,timestamp,permalink,children{media_url,media_type}',
                    'limit' => min($limit, 100),
                ];
                if ($after) $query['after'] = $after;

                $response = $this->apiClient->request('GET', $userId . '/media', [], null, $query);

                foreach ($response['data'] ?? [] as $media) {
                    $allMedia[] = $media;
                }

                $after = $response['paging']['cursors']['after'] ?? null;
            } while ($after && count($allMedia) < $limit);

            return ['data' => $allMedia, 'paging' => $response['paging'] ?? null, 'total' => count($allMedia)];
        } catch (Exception $e) {
            Log::channel('instagram')->error('Error obteniendo medios del usuario:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Obtener detalles de un medio específico
     */
    public function getMediaDetails(string $mediaId, ?string $accessToken = null): ?array
    {
        $accessToken = $accessToken ?? $this->currentAccount?->access_token;

        if (!$accessToken) {
            throw new \Exception('Access token is required');
        }

        try {
            return $this->apiClient->request(
                'GET',
                $mediaId,
                [],
                null,
                [
                    'access_token' => $accessToken,
                    'fields' => 'id,media_type,media_url,thumbnail_url,timestamp,username,caption,permalink,children{media_url,media_type}'
                ]
            );
        } catch (Exception $e) {
            Log::channel('instagram')->error('Error obteniendo detalles del medio:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function getAuthorizationUrl(
        array $scopes = [
            'instagram_business_basic',
            'instagram_business_manage_messages',
            'instagram_business_manage_comments',
            'instagram_business_content_publish',
            'instagram_business_manage_insights'
        ],
        ?string $state = null
    ): string {
        $clientId = config('instagram.meta_auth.client_id');
        $redirectUri = config('instagram.meta_auth.redirect_uri') ?: route('instagram.auth.callback');
        $scope = implode(',', $scopes);
        $state = $state ?? bin2hex(random_bytes(20));

        // Guardar el estado en la base de datos para validación posterior
        InstagramModelResolver::oauth_state()->create([
            'state' => $state,
            'service' => 'instagram',
            'ip_address' => request()->ip(),
            'expires_at' => Carbon::now()->addMinutes(10)
        ]);

        Log::channel('instagram')->debug('Estado OAuth guardado en base de datos: ' . $state);

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

    public function handleCallback(string $code, ?string $state = null): ?Model
    {
        // Validar estado OAuth
        if ($state) {
            $isValidState = InstagramModelResolver::oauth_state()->isValid($state, 'instagram')->exists();

            if (!$isValidState) {
                Log::channel('instagram')->error('El estado de OAuth no es válido o ha expirado', [
                    'received' => $state
                ]);
                return null;
            }

            // Eliminar el estado usado
            InstagramModelResolver::oauth_state()->where('state', $state)->where('service', 'instagram')->delete();
        } else {
            Log::channel('instagram')->warning('No se recibió estado OAuth en el callback');
        }

        DB::beginTransaction();

        try {
            // Crear cliente temporal para OAuth de Instagram (sin versión)
            $oauthBaseUrl = $this->resolveInstagramOAuthBaseUrl();
            $oauthClient = app(ApiClient::class)
                ->withBaseUrl($oauthBaseUrl)
                ->withVersion('');

            // Intercambiar código por token de acceso - USAR form_params (x-www-form-urlencoded)
            $response = $oauthClient->request(
                'POST',
                'oauth/access_token',
                [], // Sin parámetros en la URL
                [ // Datos en el cuerpo como form_params (x-www-form-urlencoded)
                    'form_params' => [
                        'client_id' => config('instagram.meta_auth.client_id'),
                        'client_secret' => config('instagram.meta_auth.client_secret'),
                        'grant_type' => 'authorization_code',
                        'redirect_uri' => config('instagram.meta_auth.redirect_uri') ?: route('instagram.auth.callback'),
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
                Log::channel('instagram')->error('Instagram OAuth: Formato de respuesta inesperado', ['response' => $response]);
                DB::rollBack();
                return null;
            }

            if (empty($accessToken) || empty($userId)) {
                Log::channel('instagram')->error('Instagram OAuth: Falta access_token o user_id', ['response' => $response]);
                DB::rollBack();
                return null;
            }

            // Intercambiar token de corta duración por token de larga duración
            $longLivedResponse = $this->exchangeForLongLivedToken($accessToken);
            if (!$longLivedResponse || empty($longLivedResponse['access_token'])) {
                Log::channel('instagram')->error('Instagram OAuth: Error intercambiando token por token de larga duración');
                DB::rollBack();
                return null;
            }

            $accessToken = $longLivedResponse['access_token'];
            $tokenExpiresIn = $longLivedResponse['expires_in'] ?? null;

            //Suscribir la aplicación a la cuenta empresarial de Instagram
            $this->subscribeApp($userId, $accessToken);

            $igId = null;
            try {
                $igResponse = $this->apiClient->request(
                    'GET',
                    $userId,  // ← el ID largo de la cuenta de negocio
                    [],
                    null,
                    [
                        'fields' => 'ig_id',
                        'access_token' => $accessToken
                    ]
                );
                $igId = $igResponse['ig_id'] ?? null;
            } catch (Exception $e) {
                Log::channel('instagram')->warning('No se pudo obtener ig_id durante la conexión', [
                    'account_id' => $userId,
                    'error' => $e->getMessage()
                ]);
            }

            // Obtener información del perfil usando Graph API
            $profileData = $this->apiClient->request(
                'GET',
                'me',
                [],
                null,
                [
                    'fields' => 'id,user_id,username,account_type,media_count,followers_count,follows_count,name,profile_picture_url,biography',
                    'access_token' => $accessToken
                ]
            );

            Log::channel('instagram')->debug('Información del perfil obtenida después de OAuth', ['profile_data' => $profileData]);

            $account = InstagramModelResolver::instagram_business_account()->updateOrCreate(
                ['instagram_business_account_id' => $userId],
                [
                    'access_token' => $accessToken,
                    'token_expires_in' => $tokenExpiresIn,
                    'token_obtained_at' => now(),
                    'tasks' => null,
                    'name' => $profileData['name'] ?? '',
                    'facebook_page_id' => null,
                    'permissions' => $permissions,
                ]
            );

            if (!empty($profileData)) {
                InstagramModelResolver::instagram_profile()->updateOrCreate(
                    ['instagram_business_account_id' => $userId],
                    [
                        'profile_name' => $profileData['name'] ?? '',
                        'user_id' => $profileData['user_id'] ?? null,
                        'instagram_scoped_id' => $igId,
                        'username' => $profileData['username'] ?? null,
                        'profile_picture' => $profileData['profile_picture_url'] ?? null,
                        'bio' => $profileData['biography'] ?? null,
                        'account_type' => $profileData['account_type'] ?? null,
                        'followers_count' => $profileData['followers_count'] ?? null,
                        'follows_count' => $profileData['follows_count'] ?? null,
                        'media_count' => $profileData['media_count'] ?? null,
                        'website' => $profileData['website'] ?? null,
                        'last_synced_at' => now(),
                        'raw_api_response' => $profileData
                    ]
                );
            }

            DB::commit();
            return $account;

        } catch (Exception $e) {
            Log::channel('instagram')->error('Error en OAuth Instagram:', ['error' => $e->getMessage()]);
            DB::rollBack();
            return null;
        }
    }

    /**
     * Resuelve la URL base OAuth de Instagram.
     * Si la configuración apunta por error a Facebook, aplica fallback seguro.
     */
    protected function resolveInstagramOAuthBaseUrl(): string
    {
        $configuredBaseUrl = (string) config('instagram.api.oauth_base_url', 'https://api.instagram.com');
        $host = parse_url($configuredBaseUrl, PHP_URL_HOST);

        if (!$host || str_contains($host, 'facebook.com')) {
            Log::channel('instagram')->warning('oauth_base_url inválida para Instagram. Se usará https://api.instagram.com', [
                'configured_oauth_base_url' => $configuredBaseUrl,
            ]);

            return 'https://api.instagram.com';
        }

        return rtrim($configuredBaseUrl, '/');
    }

    public function exchangeForLongLivedToken(string $shortLivedToken): ?array
    {
        try {
            // Crear cliente para endpoint de exchange (según documentación)
            $exchangeClient = app(ApiClient::class)
                ->withBaseUrl(config('instagram.api.graph_base_url', 'https://graph.instagram.com'))
                ->withVersion('');

            return $exchangeClient->request(
                'GET',
                'access_token',
                [],
                null,
                [
                    'grant_type' => 'ig_exchange_token',
                    'client_secret' => config('instagram.meta_auth.client_secret'),
                    'access_token' => $shortLivedToken,
                ]
            );

        } catch (Exception $e) {
            Log::channel('instagram')->error('Error intercambiando token:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Refrescar token de larga duración.
     * Acepta Model $account para evitar búsqueda por token (que está cifrado en BD).
     *
     * @param Model $account Cuenta de Instagram con el token actual
     * @return array|null Respuesta con access_token y expires_in, o null si falla
     */
    public function refreshLongLivedToken(Model $account): ?array
    {
        try {
            $longLivedToken = $account->access_token ?? null;
            if (!$longLivedToken) {
                Log::channel('instagram')->error('No se puede refrescar token: la cuenta no tiene access_token');
                return null;
            }

            if (!$account->token_obtained_at) {
                Log::channel('instagram')->error('No se puede refrescar token: sin fecha de obtención (token_obtained_at)');
                return null;
            }

            // Verificar que el token tenga al menos 24 horas de antigüedad (requisito de Instagram)
            $tokenAge = now()->diffInHours($account->token_obtained_at);
            if ($tokenAge < 24) {
                Log::channel('instagram')->error('No se puede refrescar token: debe tener al menos 24 horas de antigüedad', [
                    'account_id' => $account->instagram_business_account_id,
                    'token_age_hours' => $tokenAge,
                ]);
                return null;
            }

            // Verificar permiso instagram_business_basic
            if (!$this->hasPermission($account, 'instagram_business_basic')) {
                Log::channel('instagram')->error('No se puede refrescar token: falta permiso instagram_business_basic');
                return null;
            }

            // Crear cliente para endpoint de refresh (según documentación)
            $refreshClient = app(ApiClient::class)
                ->withBaseUrl(config('instagram.api.graph_base_url', 'https://graph.instagram.com'))
                ->withVersion('');

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
            Log::channel('instagram')->error('Error refrescando token:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Refrescar token de larga duración y persistir en la base de datos.
     *
     * @param Model $account Cuenta de Instagram
     * @return bool True si se refrescó y guardó correctamente
     */
    public function refreshAndStoreLongLivedToken(Model $account): bool
    {
        $response = $this->refreshLongLivedToken($account);
        if (!$response || empty($response['access_token'])) {
            return false;
        }

        $account->access_token = $response['access_token'];
        $account->token_expires_in = $response['expires_in'] ?? null;
        $account->token_obtained_at = now();
        return $account->save();
    }

    /**
     * Verificar si un token tiene un permiso específico
     */
    public function hasPermission(Model $account, string $permission): bool
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
            $account = InstagramModelResolver::instagram_business_account()->find($instagramAccountId);
            if ($account) {
                $account->facebook_page_id = $facebookPageId;
                return $account->save();
            }
            return false;
        } catch (Exception $e) {
            Log::channel('instagram')->error('Error vinculando cuenta con página de Facebook:', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function searchHashtag(string $hashtag, ?string $igUserId = null): ?array
    {
        $igUserId = $igUserId ?? $this->currentAccount?->instagram_business_account_id;
        try {
            return $this->apiClient->request('GET', 'ig_hashtag_search', [], null, [
                'user_id' => $igUserId,
                'q' => ltrim($hashtag, '#'),
                'access_token' => $this->currentAccount?->access_token,
            ]);
        } catch (Exception $e) {
            Log::channel('instagram')->error('Error searching hashtag:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function getHashtagMedia(string $hashtagId, ?string $accessToken = null): ?array
    {
        $accessToken = $accessToken ?? $this->currentAccount?->access_token;
        try {
            return $this->apiClient->request('GET', "{$hashtagId}/recent_media", [], null, [
                'fields' => 'id,media_type,media_url,caption,permalink',
                'access_token' => $accessToken,
            ]);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Suscribe una aplicación a la cuenta empresarial de Instagram actual
     *
     * @param string $userId ID de usuario de Instagram (generalmente el ID largo de la cuenta de negocio)
     * @param string $accessToken Token de acceso de Instagram
     * @param array $subscribedFields Campos a suscribir (opcional)
     * @return array |null Respuesta de la API o null si falla
     * * @throws \InvalidArgumentException Si faltan parámetros requeridos
     */
    public function subscribeApp(string $userId, string $accessToken, ?array $subscribedFields = null): array|null
    {
        if ($userId === '' || $accessToken === '') {
            throw new \InvalidArgumentException('userId and accessToken are required');
        }

        // Si no se proporcionan campos, usar los de configuración
        if ($subscribedFields === null) {
            $subscribedFields = config('instagram.webhook.subscribed_fields', []);
        }

        $subscribedFields = array_values(array_filter(array_map('trim', $subscribedFields), static fn ($field) => $field !== ''));

        $query = [
            'access_token' => $accessToken,
        ];

        if (!empty($subscribedFields)) {
            // Meta Graph espera una lista separada por comas en subscribed_fields.
            $query['subscribed_fields'] = implode(',', $subscribedFields);
        }

        Log::channel('instagram')->debug('Suscribiendo aplicación', [
            'userId' => $userId,
            'subscribed_fields' => $subscribedFields
        ]);

        $response = null;

        try {
            $response = $this->apiClient->request(
                'POST',
                $userId.'/subscribed_apps',
                [],
                null,
                $query
            );
        } catch (Exception $e) {
            Log::channel('instagram')->error('Error suscribiendo aplicación a Instagram:', ['error' => $e->getMessage()]);
        }

        Log::channel('instagram')->debug('Respuesta de subscribeApp:', [
            'response' => $response,
        ]);
        return $response;
    }

    /**
     * Cancela la suscripción de la aplicación para una cuenta empresarial de Instagram.
     *
     * Endpoint: DELETE /{ig-user-id}/subscribed_apps
     * Requiere únicamente access_token.
     *
     * @param string $userId ID de usuario de Instagram (ID largo de la cuenta de negocio)
     * @param string $accessToken Token de acceso de Instagram
     * @return array|null Respuesta de la API o null si falla
     * @throws \InvalidArgumentException Si faltan parámetros requeridos
     */
    public function unsubscribeApp(string $userId, string $accessToken): array|null
    {
        if ($userId === '' || $accessToken === '') {
            throw new \InvalidArgumentException('userId and accessToken are required');
        }

        $query = [
            'access_token' => $accessToken,
        ];

        Log::channel('instagram')->debug('Cancelando suscripción de aplicación', [
            'userId' => $userId,
        ]);

        $response = null;

        try {
            $response = $this->apiClient->request(
                'DELETE',
                $userId.'/subscribed_apps',
                [],
                null,
                $query
            );
        } catch (Exception $e) {
            Log::channel('instagram')->error('Error cancelando suscripción de aplicación a Instagram:', [
                'userId' => $userId,
                'error' => $e->getMessage(),
            ]);
        }

        Log::channel('instagram')->debug('Respuesta de unsubscribeApp:', [
            'response' => $response,
        ]);

        return $response;
    }
}