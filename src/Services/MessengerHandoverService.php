<?php

namespace ScriptDevelop\InstagramApiManager\Services;

use ScriptDevelop\InstagramApiManager\InstagramApi\ApiClient;
use Illuminate\Support\Facades\Log;
use Exception;

class MessengerHandoverService
{
    protected ApiClient $apiClient;
    protected ?string $pageAccessToken = null;

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

    public function passThreadControl(string $recipientId, string $targetAppId): bool
    {
        try {
            $this->apiClient->request('POST', 'me/pass_thread_control', [], [
                'recipient' => ['id' => $recipientId],
                'target_app_id' => (int) $targetAppId,
            ], ['access_token' => $this->pageAccessToken]);
            return true;
        } catch (Exception $e) {
            Log::channel('facebook')->error('Error passing thread control:', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function takeThreadControl(string $recipientId): bool
    {
        try {
            $this->apiClient->request('POST', 'me/take_thread_control', [], [
                'recipient' => ['id' => $recipientId],
            ], ['access_token' => $this->pageAccessToken]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getSecondaryReceivers(): ?array
    {
        try {
            return $this->apiClient->request('GET', 'me/secondary_receivers', [], null, [
                'fields' => 'id,name',
                'access_token' => $this->pageAccessToken,
            ]);
        } catch (Exception $e) {
            return null;
        }
    }
}
