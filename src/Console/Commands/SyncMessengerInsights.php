<?php

namespace ScriptDevelop\InstagramApiManager\Console\Commands;

use Illuminate\Console\Command;
use ScriptDevelop\InstagramApiManager\Services\MessengerInsightsService;
use ScriptDevelop\InstagramApiManager\Support\InstagramModelResolver;

class SyncMessengerInsights extends Command
{
    protected $signature = 'messenger:insights:sync {--days=7 : Number of days to sync}';
    protected $description = 'Sync Messenger/Facebook page insights from API';

    public function handle()
    {
        $days = (int) $this->option('days');
        $pages = InstagramModelResolver::facebook_page()->whereNotNull('access_token')->get();

        $this->info("Starting Messenger insights sync for {$pages->count()} pages (last {$days} days)...");

        foreach ($pages as $page) {
            $this->info("Processing page: {$page->page_id}");

            try {
                $insightsService = app(MessengerInsightsService::class)
                    ->withPageAccessToken($page->access_token)
                    ->withPageId($page->page_id);

                $result = $insightsService->syncInsights($page->page_id);

                if ($result) {
                    $this->info("  ✅ Page {$page->page_id} insights synced");
                } else {
                    $this->warn("  ⚠️ No insights data for page {$page->page_id}");
                }
            } catch (\Exception $e) {
                $this->error("  ❌ Error syncing page {$page->page_id}: {$e->getMessage()}");
            }
        }

        $this->info('Messenger insights sync completed');
    }
}