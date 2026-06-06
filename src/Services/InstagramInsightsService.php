<?php

namespace ScriptDevelop\InstagramApiManager\Services;

use ScriptDevelop\InstagramApiManager\InstagramApi\ApiClient;
use ScriptDevelop\InstagramApiManager\Support\InstagramModelResolver;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

class InstagramInsightsService
{
    protected ApiClient $apiClient;
    protected ?string $accessToken = null;
    protected ?string $instagramBusinessAccountId = null;

    public function __construct()
    {
        $this->apiClient = app(ApiClient::class)
            ->withBaseUrl(config('instagram.api.graph_base_url', 'https://graph.instagram.com'))
            ->withVersion(config('instagram.api.version'));
    }

    public function withAccessToken(string $token): self
    {
        $this->accessToken = $token;
        return $this;
    }

    public function withBusinessAccountId(string $instagramBusinessAccountId): self
    {
        $this->instagramBusinessAccountId = $instagramBusinessAccountId;
        return $this;
    }

    public function getAccountInsights(string $igUserId, array $metrics, string $period = 'day'): ?array
    {
        try {
            return $this->apiClient->request('GET', "{$igUserId}/insights", [], null, [
                'metric' => implode(',', $metrics),
                'period' => $period,
                'access_token' => $this->accessToken,
            ]);
        } catch (Exception $e) {
            Log::channel('instagram')->error('Error getting account insights:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function getMediaInsights(string $mediaId, array $metrics): ?array
    {
        try {
            return $this->apiClient->request('GET', "{$mediaId}/insights", [], null, [
                'metric' => implode(',', $metrics),
                'access_token' => $this->accessToken,
            ]);
        } catch (Exception $e) {
            return null;
        }
    }

    public function syncAccountInsights(string $igUserId, ?string $period = 'day', ?string $date = null): ?array
    {
        $metrics = match ($period) {
            'day' => ['followers_count', 'following_count', 'media_count', 'messages_sent', 'messages_received', 'total_comments', 'total_followers_gained', 'total_followers_lost'],
            'week' => ['followers_count', 'following_count', 'media_count'],
            'month' => ['followers_count', 'media_count'],
            default => ['followers_count', 'media_count'],
        };

        $response = $this->getAccountInsights($igUserId, $metrics, $period);

        if (!$response || empty($response['data'])) {
            return null;
        }

        $date = $date ?? Carbon::now()->toDateString();
        $statsData = [];

        foreach ($response['data'] ?? [] as $metric) {
            $values = $metric['values'] ?? [];
            if (!empty($values)) {
                $latestValue = $values[count($values) - 1]['value'] ?? 0;
                $statsData[$metric['name']] = $latestValue;
            }
        }

        if (!empty($statsData)) {
            $this->saveAccountStats($statsData, $date);
        }

        Log::channel('instagram')->info('Account insights synced', [
            'ig_user_id' => $igUserId,
            'period' => $period,
            'date' => $date,
        ]);

        return $statsData;
    }

    public function saveAccountStats(array $statsData, ?string $date = null): ?\Illuminate\Database\Eloquent\Model
    {
        $date = $date ?? Carbon::now()->toDateString();

        $existing = InstagramModelResolver::instagram_account_stats()
            ->where('instagram_business_account_id', $this->instagramBusinessAccountId)
            ->where('date', $date)
            ->first();

        $data = [
            'instagram_business_account_id' => $this->instagramBusinessAccountId,
            'date' => $date,
            'followers_count' => $statsData['followers_count'] ?? 0,
            'following_count' => $statsData['following_count'] ?? 0,
            'media_count' => $statsData['media_count'] ?? 0,
            'total_messages_sent' => $statsData['messages_sent'] ?? 0,
            'total_messages_received' => $statsData['messages_received'] ?? 0,
            'total_comments' => $statsData['total_comments'] ?? 0,
            'total_followers_gained' => $statsData['total_followers_gained'] ?? 0,
            'total_followers_lost' => $statsData['total_followers_lost'] ?? 0,
            'raw_data' => $statsData,
        ];

        if ($existing) {
            $existing->update($data);
            Log::channel('instagram')->debug('Account stats updated', ['date' => $date]);
            return $existing;
        }

        $stats = InstagramModelResolver::instagram_account_stats()->create($data);
        Log::channel('instagram')->info('Account stats saved', ['date' => $date]);
        return $stats;
    }

    public function syncMediaInsights(string $mediaId, ?string $date = null): ?array
    {
        $metrics = ['impressions', 'reach', 'likes', 'comments', 'saves', 'shares', 'video_views', 'profile_visits', 'follows'];
        $response = $this->getMediaInsights($mediaId, $metrics);

        if (!$response || empty($response['data'])) {
            return null;
        }

        $date = $date ?? Carbon::now()->toDateString();
        $statsData = [];

        foreach ($response['data'] ?? [] as $metric) {
            $values = $metric['values'] ?? [];
            if (!empty($values)) {
                $latestValue = $values[count($values) - 1]['value'] ?? 0;
                $statsData[$metric['name']] = $latestValue;
            }
        }

        if (!empty($statsData)) {
            $this->saveMediaStats($mediaId, $statsData, $date);
        }

        return $statsData;
    }

    public function saveMediaStats(string $mediaId, array $statsData, ?string $date = null): ?\Illuminate\Database\Eloquent\Model
    {
        $date = $date ?? Carbon::now()->toDateString();

        $existing = InstagramModelResolver::instagram_media_stats()
            ->where('instagram_media_id', $mediaId)
            ->where('date', $date)
            ->first();

        $data = [
            'instagram_media_id' => $mediaId,
            'instagram_business_account_id' => $this->instagramBusinessAccountId,
            'date' => $date,
            'impressions' => $statsData['impressions'] ?? 0,
            'reach' => $statsData['reach'] ?? 0,
            'likes' => $statsData['likes'] ?? 0,
            'comments' => $statsData['comments'] ?? 0,
            'saves' => $statsData['saves'] ?? 0,
            'shares' => $statsData['shares'] ?? 0,
            'video_views' => $statsData['video_views'] ?? 0,
            'profile_visits' => $statsData['profile_visits'] ?? 0,
            'follows' => $statsData['follows'] ?? 0,
            'raw_data' => $statsData,
        ];

        if ($existing) {
            $existing->update($data);
            Log::channel('instagram')->debug('Media stats updated', ['media_id' => $mediaId, 'date' => $date]);
            return $existing;
        }

        $stats = InstagramModelResolver::instagram_media_stats()->create($data);
        Log::channel('instagram')->info('Media stats saved', ['media_id' => $mediaId, 'date' => $date]);
        return $stats;
    }

    public function syncAllMediaInsights(string $accountId, int $days = 7): array
    {
        $mediaIds = [];

        try {
            $response = $this->apiClient->request('GET', "{$accountId}/media", [], null, [
                'fields' => 'id',
                'access_token' => $this->accessToken,
                'limit' => 50,
            ]);

            foreach ($response['data'] ?? [] as $media) {
                $mediaIds[] = $media['id'];
            }
        } catch (Exception $e) {
            Log::channel('instagram')->error('Error fetching media list:', ['error' => $e->getMessage()]);
            return [];
        }

        $synced = [];
        $date = Carbon::now()->toDateString();

        foreach ($mediaIds as $mediaId) {
            if ($this->syncMediaInsights($mediaId, $date)) {
                $synced[] = $mediaId;
            }
        }

        Log::channel('instagram')->info('All media insights synced', [
            'account_id' => $accountId,
            'total_media' => count($mediaIds),
            'synced' => count($synced),
        ]);

        return $synced;
    }
}