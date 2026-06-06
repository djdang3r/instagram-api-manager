<?php

namespace ScriptDevelop\InstagramApiManager\Services;

use ScriptDevelop\InstagramApiManager\InstagramApi\ApiClient;
use ScriptDevelop\InstagramApiManager\Support\InstagramModelResolver;
use Illuminate\Support\Facades\Log;
use Exception;

class InstagramContentPublishingService
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

    protected function validate(): void
    {
        if (!$this->accessToken) throw new Exception('Access token must be set.');
    }

    protected function validateCaption(string $caption): void
    {
        if (mb_strlen($caption) > 2200) {
            throw new \RuntimeException('Caption exceeds 2,200 characters (Meta limit).');
        }
        preg_match_all('/#\w+/', $caption, $hashtags);
        if (count($hashtags[0]) > 30) {
            throw new \RuntimeException('Caption exceeds 30 hashtags (Meta limit).');
        }
        preg_match_all('/@\w+/', $caption, $tags);
        if (count($tags[0]) > 20) {
            throw new \RuntimeException('Caption exceeds 20 @ tags (Meta limit).');
        }
    }

    protected function validateImageUrl(string $url): void
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg'])) {
            throw new \RuntimeException('Instagram only supports JPEG images. Got: ' . ($ext ?: 'unknown'));
        }
        $headers = @get_headers($url, 1);
        $size = (int) ($headers['Content-Length'] ?? 0);
        if ($size > 8 * 1024 * 1024) {
            throw new \RuntimeException('Image exceeds 8MB limit (Meta). Size: ' . round($size / 1024 / 1024, 1) . 'MB');
        }
    }

    protected function validateVideoUrl(string $url): void
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        if (!in_array($ext, ['mp4', 'mov', 'avi', 'webm'])) {
            throw new \RuntimeException('Video format not supported by Instagram. Use MP4. Got: ' . ($ext ?: 'unknown'));
        }
    }

    protected function createContainer(string $igUserId, array $params): ?string
    {
        $this->validate();
        try {
            $response = $this->apiClient->request('POST', "{$igUserId}/media", [], $params, [
                'access_token' => $this->accessToken,
            ]);
            return $response['id'] ?? null;
        } catch (Exception $e) {
            Log::channel('instagram')->error('Error creating media container:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    protected function publishContainer(string $igUserId, string $creationId): ?string
    {
        try {
            $response = $this->apiClient->request('POST', "{$igUserId}/media_publish", [], [
                'creation_id' => $creationId,
            ], ['access_token' => $this->accessToken]);
            return $response['id'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function publishImage(string $igUserId, string $imageUrl, ?string $caption = null): ?array
    {
        $this->validateImageUrl($imageUrl);
        if ($caption) $this->validateCaption($caption);
        $params = ['image_url' => $imageUrl];
        if ($caption) $params['caption'] = $caption;

        $creationId = $this->createContainer($igUserId, $params);
        if (!$creationId) return null;

        $mediaId = $this->publishContainer($igUserId, $creationId);

        if ($mediaId) {
            $result = ['media_id' => $mediaId, 'creation_id' => $creationId];
            $this->savePost([
                'id' => $mediaId,
                'caption' => $caption,
                'media_type' => 'IMAGE',
                'status' => 'published',
                'timestamp' => time(),
            ]);
            return $result;
        }

        return null;
    }

    public function publishVideo(string $igUserId, string $videoUrl, ?string $caption = null): ?array
    {
        $this->validateVideoUrl($videoUrl);
        if ($caption) $this->validateCaption($caption);
        $params = ['video_url' => $videoUrl, 'media_type' => 'REELS'];
        if ($caption) $params['caption'] = $caption;

        $creationId = $this->createContainer($igUserId, $params);
        if (!$creationId) return null;

        $mediaId = $this->publishContainer($igUserId, $creationId);

        if ($mediaId) {
            $result = ['media_id' => $mediaId, 'creation_id' => $creationId];
            $this->savePost([
                'id' => $mediaId,
                'caption' => $caption,
                'media_type' => 'VIDEO',
                'product_type' => 'reels',
                'status' => 'published',
                'timestamp' => time(),
            ]);
            return $result;
        }

        return null;
    }

    public function publishCarousel(string $igUserId, array $mediaItems, ?string $caption = null): ?array
    {
        if ($caption) $this->validateCaption($caption);
        if (count($mediaItems) > 10) {
            throw new \RuntimeException('Carousel exceeds 10 items (Meta limit).');
        }
        $childrenIds = [];
        foreach ($mediaItems as $item) {
            $childId = $this->createContainer($igUserId, [
                'image_url' => $item['image_url'] ?? $item['video_url'] ?? null,
                'video_url' => $item['video_url'] ?? null,
                'media_type' => isset($item['video_url']) ? 'REELS' : null,
                'is_carousel_item' => true,
            ]);
            if ($childId) $childrenIds[] = $childId;
        }

        if (empty($childrenIds)) return null;

        $params = ['media_type' => 'CAROUSEL', 'children' => $childrenIds];
        if ($caption) $params['caption'] = $caption;

        $creationId = $this->createContainer($igUserId, $params);
        if (!$creationId) return null;

        $mediaId = $this->publishContainer($igUserId, $creationId);

        if ($mediaId) {
            $result = ['media_id' => $mediaId, 'creation_id' => $creationId];
            $this->savePost([
                'id' => $mediaId,
                'caption' => $caption,
                'media_type' => 'CAROUSEL',
                'children_ids' => $childrenIds,
                'status' => 'published',
                'timestamp' => time(),
            ]);
            return $result;
        }

        return null;
    }

    public function getMediaStatus(string $igUserId, string $creationId): ?array
    {
        $this->validate();
        try {
            return $this->apiClient->request('GET', $creationId, [], null, [
                'fields' => 'status_code,status',
                'access_token' => $this->accessToken,
            ]);
        } catch (Exception $e) {
            return null;
        }
    }

    public function savePost(array $postData): ?\Illuminate\Database\Eloquent\Model
    {
        $mediaId = $postData['id'] ?? null;

        if (!$mediaId) {
            return null;
        }

        $existing = InstagramModelResolver::instagram_post()
            ->where('media_id', $mediaId)
            ->first();

        $timestamp = isset($postData['timestamp'])
            ? date('Y-m-d H:i:s', is_numeric($postData['timestamp']) ? $postData['timestamp'] : strtotime($postData['timestamp']))
            : now();

        $data = [
            'media_id' => $mediaId,
            'instagram_business_account_id' => $this->instagramBusinessAccountId,
            'caption' => $postData['caption'] ?? null,
            'media_type' => $postData['media_type'] ?? 'IMAGE',
            'media_url' => $postData['media_url'] ?? $postData['thumbnail_url'] ?? null,
            'permalink' => $postData['permalink'] ?? null,
            'thumbnail_url' => $postData['thumbnail_url'] ?? null,
            'timestamp' => $timestamp,
            'username' => $postData['username'] ?? null,
            'like_count' => $postData['like_count'] ?? 0,
            'comments_count' => $postData['comments_count'] ?? 0,
            'status' => $postData['status'] ?? 'published',
            'scheduled_at' => isset($postData['scheduled_at']) ? date('Y-m-d H:i:s', is_numeric($postData['scheduled_at']) ? $postData['scheduled_at'] : strtotime($postData['scheduled_at'])) : null,
            'published_at' => $postData['status'] === 'published' ? now() : null,
            'product_type' => $postData['product_type'] ?? null,
            'children_ids' => $postData['children_ids'] ?? null,
            'raw_data' => $postData,
        ];

        if ($existing) {
            $existing->update($data);
            Log::channel('instagram')->debug('Post updated', ['media_id' => $mediaId]);
            return $existing;
        }

        $post = InstagramModelResolver::instagram_post()->create($data);
        Log::channel('instagram')->info('Post saved', ['media_id' => $mediaId]);
        return $post;
    }

    public function syncPost(string $mediaId): ?\Illuminate\Database\Eloquent\Model
    {
        $this->validate();

        try {
            $response = $this->apiClient->request('GET', $mediaId, [], null, [
                'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username,like_count,comments_count,product_type',
                'access_token' => $this->accessToken,
            ]);

            if (!empty($response['id'])) {
                return $this->savePost($response);
            }

            return null;
        } catch (Exception $e) {
            Log::channel('instagram')->error('Error syncing post:', ['media_id' => $mediaId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function syncPosts(string $accountId, int $limit = 50): array
    {
        $this->validate();

        try {
            $response = $this->apiClient->request('GET', "{$accountId}/media", [], null, [
                'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username,like_count,comments_count,product_type',
                'access_token' => $this->accessToken,
                'limit' => $limit,
            ]);

            $saved = 0;
            $synced = [];

            foreach ($response['data'] ?? [] as $postData) {
                if ($this->savePost($postData)) {
                    $saved++;
                    $synced[] = $postData['id'];
                }
            }

            Log::channel('instagram')->info('Posts synced', [
                'account_id' => $accountId,
                'total' => count($response['data'] ?? []),
                'saved' => $saved,
            ]);

            return $synced;
        } catch (Exception $e) {
            Log::channel('instagram')->error('Error syncing posts:', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function schedulePost(string $igUserId, string $imageUrl, ?string $caption, int $timestamp): ?array
    {
        $this->validateCaption($caption ?? '');
        $this->validateImageUrl($imageUrl);

        $params = [
            'image_url' => $imageUrl,
            'caption' => $caption,
            'publish_time' => $timestamp,
            'timezone' => 'UTC',
        ];

        $creationId = $this->createContainer($igUserId, $params);

        if ($creationId) {
            $this->savePost([
                'id' => $creationId,
                'caption' => $caption,
                'media_type' => 'IMAGE',
                'scheduled_at' => $timestamp,
                'status' => 'scheduled',
                'timestamp' => $timestamp,
            ]);

            return ['creation_id' => $creationId, 'scheduled_at' => date('Y-m-d H:i:s', $timestamp)];
        }

        return null;
    }
}