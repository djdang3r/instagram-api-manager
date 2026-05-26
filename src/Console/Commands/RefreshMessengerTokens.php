<?php

namespace ScriptDevelop\InstagramApiManager\Console\Commands;

use Illuminate\Console\Command;
use ScriptDevelop\InstagramApiManager\Models\FacebookPage;
use ScriptDevelop\InstagramApiManager\Services\FacebookAccountService;

class RefreshMessengerTokens extends Command
{
    protected $signature = 'messenger:refresh-tokens';
    protected $description = 'Refrescar tokens de páginas de Facebook Messenger';

    public function handle(): int
    {
        $service = app(FacebookAccountService::class);
        $pages = FacebookPage::all();

        foreach ($pages as $page) {
            $this->info("Procesando página: {$page->name} ({$page->page_id})");

            if ($page->access_token) {
                if ($service->refreshAndStoreLongLivedToken($page)) {
                    $this->info("✅ Token actualizado para {$page->name}");
                } else {
                    $this->error("❌ Error refrescando token para {$page->name}");
                }
            } else {
                $this->warn("⚠️ Sin token para {$page->name}");
            }
        }

        return self::SUCCESS;
    }
}
