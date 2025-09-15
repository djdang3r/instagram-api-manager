<?php

namespace ScriptDevelop\InstagramApiManager\Console\Commands;

use Illuminate\Console\Command;
use ScriptDevelop\InstagramApiManager\Services\InstagramMessageService;
use ScriptDevelop\InstagramApiManager\Support\InstagramModelResolver;

class SyncInstagramConversations extends Command
{
    protected $signature = 'instagram:conversations:sync';
    protected $description = 'Sync Instagram conversations from API';

    public function handle()
    {
        $accounts = InstagramModelResolver::instagram_business_account()->whereNotNull('access_token')->get();

        foreach ($accounts as $account) {
            $this->info("Syncing conversations for account: {$account->instagram_business_account_id}");
            
            try {
                app(InstagramMessageService::class)
                    ->withAccessToken($account->access_token)
                    ->withInstagramUserId($account->instagram_business_account_id)
                    ->syncConversations($account->access_token, $account->instagram_business_account_id);
                
                $this->info("Successfully synced conversations for account: {$account->instagram_business_account_id}");
            } catch (\Exception $e) {
                $this->error("Error syncing conversations for account {$account->instagram_business_account_id}: {$e->getMessage()}");
            }
        }
        
        $this->info('Instagram conversations sync completed');
    }
}