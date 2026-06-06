<?php

namespace ScriptDevelop\InstagramApiManager\Console\Commands;

use Illuminate\Console\Command;
use ScriptDevelop\InstagramApiManager\Services\InstagramInsightsService;
use ScriptDevelop\InstagramApiManager\Support\InstagramModelResolver;

class SyncInstagramStats extends Command
{
    protected $signature = 'instagram:stats:sync {--days=7 : Number of days to sync}';
    protected $description = 'Sync Instagram account and media insights from API';

    public function handle()
    {
        $days = (int) $this->option('days');
        $accounts = InstagramModelResolver::instagram_business_account()->whereNotNull('access_token')->get();

        $this->info("Starting Instagram stats sync for {$accounts->count()} accounts (last {$days} days)...");

        foreach ($accounts as $account) {
            $this->info("Processing account: {$account->instagram_business_account_id}");

            try {
                $insightsService = app(InstagramInsightsService::class)
                    ->withAccessToken($account->access_token)
                    ->withBusinessAccountId($account->instagram_business_account_id);

                $insightsService->syncAccountInsights($account->instagram_business_account_id, 'day');

                $mediaIds = $this->getMediaIds($account->instagram_business_account_id, $account->access_token);
                $syncedMedia = 0;

                foreach ($mediaIds as $mediaId) {
                    if ($insightsService->syncMediaInsights($mediaId)) {
                        $syncedMedia++;
                    }
                }

                $this->info("  - Account stats: synced");
                $this->info("  - Media insights: {$syncedMedia} media synced");
                $this->info("  ✅ Account {$account->instagram_business_account_id} completed");
            } catch (\Exception $e) {
                $this->error("  ❌ Error syncing account {$account->instagram_business_account_id}: {$e->getMessage()}");
            }
        }

        $this->info('Instagram stats sync completed');
    }

    protected function getMediaIds(string $accountId, string $accessToken): array
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

            return array_column($response['data'] ?? [], 'id');
        } catch (\Exception $e) {
            $this->warn("  Could not fetch media IDs for account {$accountId}: {$e->getMessage()}");
            return [];
        }
    }
}