<?php

namespace ScriptDevelop\InstagramApiManager\Services;

use ScriptDevelop\InstagramApiManager\InstagramApi\ApiClient;
use Illuminate\Support\Facades\Log;
use Exception;

class MessengerProfileService
{
    protected ApiClient $apiClient;
    protected ?string $pageAccessToken = null;
    protected ?string $pageId = null;

    public function __construct()
    {
        $this->apiClient = app(ApiClient::class)
            ->withBaseUrl(config('facebook.api.base_url'))
            ->withVersion(config('facebook.api.version'));
    }

    public function withPageAccessToken(string $token): self
    {
        $this->pageAccessToken = $token;
        return $this;
    }

    public function withPageId(string $pageId): self
    {
        $this->pageId = $pageId;
        return $this;
    }

    protected function validateCredentials(): void
    {
        if (!$this->pageAccessToken) {
            throw new Exception('Page Access Token must be set.');
        }
    }

    protected function request(string $method, array $data = [], array $query = []): ?array
    {
        $this->validateCredentials();
        $query['access_token'] = $this->pageAccessToken;
        try {
            return $this->apiClient->request(
                $method,
                'me/messenger_profile',
                [],
                $method === 'POST' ? $data : null,
                $method !== 'POST' ? array_merge($query, $data) : $query
            );
        } catch (Exception $e) {
            Log::channel('facebook')->error('Messenger Profile API error:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // ── Greeting ──────────────────────────────────────────────────

    public function setGreeting(array $greetings): bool
    {
        return $this->request('POST', ['greeting' => $greetings]) !== null;
    }

    public function getGreeting(): ?array
    {
        $response = $this->request('GET', [], ['fields' => 'greeting']);
        return $response['data'][0]['greeting'] ?? null;
    }

    public function deleteGreeting(): bool
    {
        return $this->request('DELETE', ['fields' => 'greeting']) !== null;
    }

    // ── Get Started Button ────────────────────────────────────────

    public function setGetStartedButton(string $payload): bool
    {
        return $this->request('POST', ['get_started' => ['payload' => $payload]]) !== null;
    }

    public function getGetStartedButton(): ?string
    {
        $response = $this->request('GET', [], ['fields' => 'get_started']);
        return $response['data'][0]['get_started']['payload'] ?? null;
    }

    public function deleteGetStartedButton(): bool
    {
        return $this->request('DELETE', ['fields' => 'get_started']) !== null;
    }

    // ── Persistent Menu ───────────────────────────────────────────

    public function setPersistentMenu(array $menu): bool
    {
        return $this->request('POST', ['persistent_menu' => $menu]) !== null;
    }

    public function getPersistentMenu(): ?array
    {
        $response = $this->request('GET', [], ['fields' => 'persistent_menu']);
        return $response['data'][0]['persistent_menu'] ?? null;
    }

    public function deletePersistentMenu(): bool
    {
        return $this->request('DELETE', ['fields' => 'persistent_menu']) !== null;
    }

    // ── Ice Breakers ──────────────────────────────────────────────

    public function setIceBreakers(array $questions): bool
    {
        return $this->request('POST', ['ice_breakers' => $questions]) !== null;
    }

    public function getIceBreakers(): ?array
    {
        $response = $this->request('GET', [], ['fields' => 'ice_breakers']);
        return $response['data'][0]['ice_breakers'] ?? null;
    }

    public function deleteIceBreakers(): bool
    {
        return $this->request('DELETE', ['fields' => 'ice_breakers']) !== null;
    }

    // ── Personas ────────────────────────────────────────────────

    public function createPersona(string $name, string $profilePictureUrl): ?string
    {
        $this->validateCredentials();
        try {
            $response = $this->apiClient->request('POST', 'me/personas', [], [
                'name' => $name,
                'profile_picture_url' => $profilePictureUrl,
            ], ['access_token' => $this->pageAccessToken]);
            return $response['id'] ?? null;
        } catch (Exception $e) {
            Log::channel('facebook')->error('Error creating persona:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function getPersonas(): ?array
    {
        $this->validateCredentials();
        try {
            return $this->apiClient->request('GET', 'me/personas', [], null, [
                'access_token' => $this->pageAccessToken,
            ]);
        } catch (Exception $e) {
            return null;
        }
    }

    public function deletePersona(string $personaId): bool
    {
        $this->validateCredentials();
        try {
            $this->apiClient->request('DELETE', $personaId, [], null, [
                'access_token' => $this->pageAccessToken,
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
