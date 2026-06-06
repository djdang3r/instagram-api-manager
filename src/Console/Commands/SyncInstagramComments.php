<?php

namespace ScriptDevelop\InstagramApiManager\Console\Commands;

use Illuminate\Console\Command;
use ScriptDevelop\InstagramApiManager\Services\InstagramCommentService;
use ScriptDevelop\InstagramApiManager\Support\InstagramModelResolver;

class SyncInstagramComments extends Command
{
    protected $signature = 'instagram:comments:sync {--media_id= : Specific media ID to sync}';
    protected $description = 'Sync Instagram comments from API for all posts or specific media';

    public function handle()
    {
        $mediaId = $this->option('media_id');
        $accounts = InstagramModelResolver::instagram_business_account()->whereNotNull('access_token')->get();

        $this->info('Starting Instagram comments sync...');

        foreach ($accounts as $account) {
            $this->info("Processing account: {$account->instagram_business_account_id}");

            try {
                $commentService = app(InstagramCommentService::class)
                    ->withAccessToken($account->access_token)
                    ->withBusinessAccountId($account->instagram_business_account_id);

                if ($mediaId) {
                    $this->syncCommentsForMedia($commentService, $mediaId);
                } else {
                    $this->syncCommentsForAllMedia($commentService, $account);
                }

                $this->info("  ✅ Account {$account->instagram_business_account_id} completed");
            } catch (\Exception $e) {
                $this->error("  ❌ Error syncing account {$account->instagram_business_account_id}: {$e->getMessage()}");
            }
        }

        $this->info('Instagram comments sync completed');
    }

    protected function syncCommentsForMedia(InstagramCommentService $commentService, string $mediaId): void
    {
        $result = $commentService->syncCommentsWithReplies($mediaId, 200);

        $this->info("  Media {$mediaId}:");
        $this->info("    - Saved: {$result['saved_count']}");
        $this->info("    - Updated: {$result['updated_count']}");
    }

    protected function syncCommentsForAllMedia(InstagramCommentService $commentService, $account): void
    {
        try {
            $mediaIds = $this->getMediaIds($account->instagram_business_account_id, $account->access_token);

            $this->info("  Found {$mediaIds->count()} media items");

            foreach ($mediaIds as $mediaId) {
                $result = $commentService->syncCommentsWithReplies($mediaId, 100);

                if ($result['saved_count'] > 0 || $result['updated_count'] > 0) {
                    $this->info("  Media {$mediaId}: saved={$result['saved_count']}, updated={$result['updated_count']}");
                }
            }
        } catch (\Exception $e) {
            $this->warn("  Could not fetch media list: {$e->getMessage()}");
        }
    }

    protected function getMediaIds(string $accountId, string $accessToken): \Illuminate\Support\Collection
    {
        try {
            $response = app(\ScriptDevelop\InstagramApiManager\InstagramApi\ApiClient::class)
                ->withBaseUrl(config('instagram.api.graph_base_url'))
                ->withVersion(config('instagram.api.version'))
                ->request('GET', "{$accountId}/media", [], null, [
                    'fields' => 'id',
                    'access_token' => $accessToken,
                    'limit' => 50,
                ]);

            return collect($response['data'] ?? [])->pluck('id');
        } catch (\Exception $e) {
            $this->warn("  Could not fetch media IDs: {$e->getMessage()}");
            return collect();
        }
    }
}