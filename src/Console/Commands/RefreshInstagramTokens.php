<?php

namespace ScriptDevelop\InstagramApiManager\Console\Commands;

use Illuminate\Console\Command;
use ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount;
use ScriptDevelop\InstagramApiManager\Services\InstagramAccountService;

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
        
        $accounts = InstagramBusinessAccount::all();

        foreach ($accounts as $account) {
            $this->info("Procesando cuenta: {$account->instagram_business_account_id}");

            if ($account->access_token) {
                $response = $this->instagramService->refreshLongLivedToken($account->access_token);
                if ($response && isset($response['access_token'])) {
                    $account->access_token = $response['access_token'];
                    $account->token_expires_in = $response['expires_in'] ?? null;
                    $account->save();

                    $this->info("Token actualizado para cuenta {$account->instagram_business_account_id}");
                } else {
                    $this->error("Error refrescando token para cuenta {$account->instagram_business_account_id}");
                }
            }
        }

        return 0;
    }
}
