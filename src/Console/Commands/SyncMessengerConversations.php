<?php

namespace ScriptDevelop\InstagramApiManager\Console\Commands;

use Illuminate\Console\Command;
use ScriptDevelop\InstagramApiManager\Support\InstagramModelResolver;
use ScriptDevelop\InstagramApiManager\Services\MessengerMessageService;

class SyncMessengerConversations extends Command
{
    protected $signature = 'messenger:sync-conversations';
    protected $description = 'Sincronizar conversaciones de Messenger desde la API';

    public function handle(): int
    {
        $service = app(MessengerMessageService::class);
        $pages = InstagramModelResolver::facebook_page()->whereNotNull('access_token')->get();

        foreach ($pages as $page) {
            $this->info("Sincronizando conversaciones para: {$page->name} ({$page->page_id})");

            $result = $service->syncConversations($page->page_id, $page->access_token);

            if ($result) {
                $count = count($result['data'] ?? []);
                $this->info("✅ {$count} conversaciones sincronizadas para {$page->name}");
            } else {
                $this->error("❌ Error sincronizando {$page->name}");
            }
        }

        return self::SUCCESS;
    }
}
