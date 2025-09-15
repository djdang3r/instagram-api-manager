<?php

namespace ScriptDevelop\InstagramApiManager\Console\Commands;

use Illuminate\Console\Command;
use ScriptDevelop\InstagramApiManager\Support\InstagramModelResolver;

class CleanupOauthStates extends Command
{
    protected $signature = 'oauth:cleanup';
    protected $description = 'Clean up expired OAuth states';

    public function handle()
    {
        $count = InstagramModelResolver::oauth_state()->cleanupExpired();
        $this->info("Cleaned up $count expired OAuth states.");
    }
}