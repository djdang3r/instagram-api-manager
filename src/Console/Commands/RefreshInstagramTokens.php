<?php

namespace ScriptDevelop\InstagramApiManager\Console\Commands;

use Illuminate\Console\Command;
use ScriptDevelop\InstagramApiManager\Services\InstagramAccountService;
use ScriptDevelop\InstagramApiManager\Support\InstagramModelResolver;

class RefreshInstagramTokens extends Command
{
    protected $signature = 'instagram:refresh-tokens';
    protected $description = 'Refrescar tokens largos de Instagram para evitar expiraciones';

    protected InstagramAccountService $instagramService;

    public function __construct(InstagramAccountService $instagramService)
    {
        parent::__construct();
        $this->instagramService = $instagramService;
    }

    public function handle()
    {
        $instagramService = app(InstagramAccountService::class);

        $accounts = InstagramModelResolver::instagram_business_account()->all();

        foreach ($accounts as $account) {
            $this->info("Procesando cuenta: {$account->instagram_business_account_id}");

            if ($account->access_token) {
                if ($this->instagramService->refreshAndStoreLongLivedToken($account)) {
                    $this->info("Token actualizado para cuenta {$account->instagram_business_account_id}");
                } else {
                    $this->error("Error refrescando token para cuenta {$account->instagram_business_account_id}");
                }
            }
        }

        return 0;
    }
}
