<?php

namespace ScriptDevelop\InstagramApiManager\Services;

use ScriptDevelop\InstagramApiManager\InstagramApi\ApiClient;
use Illuminate\Support\Facades\Log;
use Exception;

class InstagramPersistentMenuService
{
    protected ApiClient $apiClient;
    protected ?string $accessToken = null;
    protected ?string $instagramUserId = null;

    public function __construct()
    {
        $this->apiClient = new ApiClient(
            config('instagram.graph_base_url', 'https://graph.facebook.com'),
            config('instagram.api_version', 'v19.0'),
            (int) config('instagram.timeout', 30)
        );
    }

    /**
     * Establecer el token de acceso para las operaciones
     */
    public function withAccessToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    /**
     * Establecer el ID de usuario de Instagram para las operaciones
     */
    public function withInstagramUserId(string $instagramUserId): self
    {
        $this->instagramUserId = $instagramUserId;
        return $this;
    }

    /**
     * Validar que las credenciales estén establecidas
     */
    protected function validateCredentials(): void
    {
        if (!$this->accessToken) {
            throw new Exception('Access token is required. Use withAccessToken() method first.');
        }

        if (!$this->instagramUserId) {
            throw new Exception('Instagram user ID is required. Use withInstagramUserId() method first.');
        }
    }

    /**
     * Establecer un menú persistente
     */
    public function setPersistentMenu(array $menus): ?array
    {
        $this->validateCredentials();

        // Validar la estructura del menú
        $this->validatePersistentMenu($menus);

        $payload = [
            'platform' => 'instagram',
            'persistent_menu' => $menus
        ];

        try {
            $response = $this->apiClient->request(
                'POST',
                $this->instagramUserId . '/messenger_profile',
                [],
                $payload,
                [
                    'access_token' => $this->accessToken
                ]
            );

            Log::info('Persistent menu set successfully', ['response' => $response]);
            return $response;
        } catch (Exception $e) {
            Log::error('Error setting persistent menu:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Obtener el menú persistente actual
     */
    public function getPersistentMenu(): ?array
    {
        $this->validateCredentials();

        try {
            $response = $this->apiClient->request(
                'GET',
                $this->instagramUserId . '/messenger_profile',
                [],
                null,
                [
                    'access_token' => $this->accessToken,
                    'fields' => 'persistent_menu',
                    'platform' => 'instagram'
                ]
            );

            Log::info('Persistent menu retrieved successfully', ['response' => $response]);
            return $response;
        } catch (Exception $e) {
            Log::error('Error getting persistent menu:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Eliminar el menú persistente
     */
    public function deletePersistentMenu(): ?array
    {
        $this->validateCredentials();

        try {
            $response = $this->apiClient->request(
                'DELETE',
                $this->instagramUserId . '/messenger_profile',
                [],
                null,
                [
                    'access_token' => $this->accessToken,
                    'fields' => 'persistent_menu',
                    'platform' => 'instagram'
                ]
            );

            Log::info('Persistent menu deleted successfully', ['response' => $response]);
            return $response;
        } catch (Exception $e) {
            Log::error('Error deleting persistent menu:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Validar la estructura del menú persistente
     */
    protected function validatePersistentMenu(array $menus): bool
    {
        foreach ($menus as $menu) {
            if (!isset($menu['locale'])) {
                throw new Exception('Cada menú debe tener un locale');
            }

            if (!isset($menu['composer_input_disabled'])) {
                throw new Exception('Cada menú debe tener composer_input_disabled');
            }

            if (!isset($menu['call_to_actions']) || !is_array($menu['call_to_actions'])) {
                throw new Exception('Cada menú debe tener un array de call_to_actions');
            }

            if (count($menu['call_to_actions']) > 5) {
                throw new Exception('El menú no puede tener más de 5 elementos');
            }

            foreach ($menu['call_to_actions'] as $button) {
                if (!isset($button['type']) || !in_array($button['type'], ['web_url', 'postback'])) {
                    throw new Exception('Tipo de botón no válido. Solo se permiten web_url y postback');
                }

                if (!isset($button['title']) || empty($button['title'])) {
                    throw new Exception('Cada botón debe tener un título');
                }

                if (strlen($button['title']) > 30) {
                    throw new Exception('El título del botón no puede exceder 30 caracteres');
                }

                if ($button['type'] == 'web_url') {
                    if (!isset($button['url']) || empty($button['url'])) {
                        throw new Exception('Los botones web_url requieren una URL');
                    }
                    if (!isset($button['webview_height_ratio'])) {
                        throw new Exception('Los botones web_url requieren webview_height_ratio');
                    }
                } elseif ($button['type'] == 'postback') {
                    if (!isset($button['payload']) || empty($button['payload'])) {
                        throw new Exception('Los botones postback requieren un payload');
                    }
                }
            }
        }

        return true;
    }

    /**
     * Crear un menú localizado
     */
    public function createLocalizedMenu(string $locale, bool $composerInputDisabled, array $callToActions): array
    {
        return [
            'locale' => $locale,
            'composer_input_disabled' => $composerInputDisabled,
            'call_to_actions' => $callToActions
        ];
    }

    /**
     * Crear un botón de URL para el menú
     */
    public function createUrlButton(string $title, string $url, string $webviewHeightRatio = 'full'): array
    {
        return [
            'type' => 'web_url',
            'title' => $title,
            'url' => $url,
            'webview_height_ratio' => $webviewHeightRatio
        ];
    }

    /**
     * Crear un botón de postback para el menú
     */
    public function createPostbackButton(string $title, string $payload): array
    {
        return [
            'type' => 'postback',
            'title' => $title,
            'payload' => $payload
        ];
    }
}