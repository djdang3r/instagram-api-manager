<?php

namespace ScriptDevelop\InstagramApiManager\Services;

use ScriptDevelop\InstagramApiManager\InstagramApi\ApiClient;
use ScriptDevelop\InstagramApiManager\Support\InstagramModelResolver;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

class MessengerInsightsService
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

    protected function validateToken(): void
    {
        if (!$this->pageAccessToken) {
            throw new Exception('Page Access Token must be set.');
        }
    }

    protected function getInsights(string $pageId, string $metric, string $since, string $until): ?array
    {
        $this->validateToken();
        try {
            return $this->apiClient->request('GET', "{$pageId}/insights", [], null, [
                'metric' => $metric,
                'period' => 'day',
                'since' => $since,
                'until' => $until,
                'access_token' => $this->pageAccessToken,
            ]);
        } catch (Exception $e) {
            Log::channel('facebook')->error('Error getting insights:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function getTotalMessages(string $pageId, string $since, string $until): ?array
    {
        return $this->getInsights($pageId, 'page_messages_total', $since, $until);
    }

    public function getNewConversations(string $pageId, string $since, string $until): ?array
    {
        return $this->getInsights($pageId, 'page_messages_new_conversations', $since, $until);
    }

    public function getBlockedConversations(string $pageId, string $since, string $until): ?array
    {
        return $this->getInsights($pageId, 'page_messages_blocked_conversations', $since, $until);
    }

    public function syncInsights(string $pageId, ?string $since = null, ?string $until = null): ?array
    {
        $this->validateToken();

        $since = $since ?? Carbon::now()->subDays(7)->toDateString();
        $until = $until ?? Carbon::now()->toDateString();

        $metrics = [
            'page_messages_total',
            'page_messages_new_conversations',
            'page_messages_blocked_conversations',
            'page_messages_reported_conversations',
            'page_views_total',
            'page_impressions_total',
        ];

        $allData = [];

        foreach ($metrics as $metric) {
            $data = $this->getInsights($pageId, $metric, $since, $until);
            if ($data && !empty($data['data'])) {
                $allData[$metric] = $data['data'];
            }
        }

        if (!empty($allData)) {
            $this->saveInsights($pageId, $allData, $since, $until);
        }

        Log::channel('facebook')->info('Messenger insights synced', [
            'page_id' => $pageId,
            'since' => $since,
            'until' => $until,
        ]);

        return $allData;
    }

    public function saveInsights(string $pageId, array $insightsData, string $since, string $until): ?\Illuminate\Database\Eloquent\Model
    {
        $date = Carbon::parse($since)->format('Y-m-d');

        $processedData = $this->processInsightsData($insightsData);

        $existing = InstagramModelResolver::messenger_insights()
            ->where('page_id', $pageId)
            ->where('date', $date)
            ->first();

        $data = [
            'page_id' => $pageId,
            'date' => $date,
            'total_conversations' => $processedData['total_conversations'] ?? 0,
            'total_messages_sent' => $processedData['messages_sent'] ?? 0,
            'total_messages_received' => $processedData['messages_received'] ?? 0,
            'total_blocked_contacts' => $processedData['blocked'] ?? 0,
            'total_reported_contacts' => $processedData['reported'] ?? 0,
            'page_views' => $processedData['page_views'] ?? 0,
            'page_impressions' => $processedData['impressions'] ?? 0,
            'raw_data' => $insightsData,
        ];

        if ($existing) {
            $existing->update($data);
            Log::channel('facebook')->debug('Messenger insights updated', ['page_id' => $pageId, 'date' => $date]);
            return $existing;
        }

        $insights = InstagramModelResolver::messenger_insights()->create($data);
        Log::channel('facebook')->info('Messenger insights saved', ['page_id' => $pageId, 'date' => $date]);
        return $insights;
    }

    protected function processInsightsData(array $insightsData): array
    {
        $result = [
            'total_conversations' => 0,
            'messages_sent' => 0,
            'messages_received' => 0,
            'blocked' => 0,
            'reported' => 0,
            'page_views' => 0,
            'impressions' => 0,
        ];

        foreach ($insightsData as $metric => $values) {
            if (empty($values) || !is_array($values)) {
                continue;
            }

            $totalValue = 0;
            foreach ($values as $entry) {
                if (isset($entry['value'])) {
                    $totalValue += (int) $entry['value'];
                }
            }

            switch ($metric) {
                case 'page_messages_total':
                    $result['messages_sent'] = $totalValue;
                    break;
                case 'page_messages_new_conversations':
                    $result['total_conversations'] = $totalValue;
                    break;
                case 'page_messages_blocked_conversations':
                    $result['blocked'] = $totalValue;
                    break;
                case 'page_messages_reported_conversations':
                    $result['reported'] = $totalValue;
                    break;
                case 'page_views_total':
                    $result['page_views'] = $totalValue;
                    break;
                case 'page_impressions_total':
                    $result['impressions'] = $totalValue;
                    break;
            }
        }

        return $result;
    }

    public function syncAllPagesInsights(array $pageIds, ?string $since = null, ?string $until = null): array
    {
        $synced = [];

        foreach ($pageIds as $pageId) {
            if ($this->syncInsights($pageId, $since, $until)) {
                $synced[] = $pageId;
            }
        }

        Log::channel('facebook')->info('All pages insights synced', [
            'total_pages' => count($pageIds),
            'synced' => count($synced),
        ]);

        return $synced;
    }
}