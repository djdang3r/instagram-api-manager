<?php

namespace ScriptDevelop\InstagramApiManager\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $payload;
    public string $platform;
    public ?string $hubSignature;

    public function __construct(array $payload, string $platform = 'instagram', ?string $hubSignature = null)
    {
        $this->payload = $payload;
        $this->platform = $platform;
        $this->hubSignature = $hubSignature;
    }

    public function handle(): void
    {
        if ($this->platform === 'facebook') {
            $service = app(\ScriptDevelop\InstagramApiManager\Services\MessengerMessageService::class);
            $processor = new \ScriptDevelop\InstagramApiManager\Services\WebhookProcessors\MessengerWebhookProcessor($service);
        } else {
            $service = app(\ScriptDevelop\InstagramApiManager\Services\InstagramMessageService::class);
            $processor = new \ScriptDevelop\InstagramApiManager\Services\WebhookProcessors\BaseWebhookProcessor($service);
        }

        $request = \Illuminate\Http\Request::create('/', 'POST', $this->payload);
        if ($this->hubSignature) {
            $request->headers->set('X-Hub-Signature-256', $this->hubSignature);
        }

        $processor->processWebhookPayload($request);
    }
}
