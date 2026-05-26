<?php

namespace ScriptDevelop\InstagramApiManager\Console\Commands;

use Illuminate\Console\Command;
use ScriptDevelop\InstagramApiManager\Support\InstagramModelResolver;

class SyncMessengerConversations extends Command
{
    protected $signature = 'messenger:sync-conversations';
    protected $description = 'Sincronizar conversaciones de Messenger desde la API';

    public function handle(): int
    {
        $pages = InstagramModelResolver::facebook_page()->whereNotNull('access_token')->get();

        foreach ($pages as $page) {
            $this->info("Sincronizando conversaciones para: {$page->name} ({$page->page_id})");
            $this->warn("Método syncConversations no implementado aún en MessengerMessageService");
        }

        return self::SUCCESS;
    }
}
