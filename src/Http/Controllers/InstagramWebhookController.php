<?php

namespace ScriptDevelop\InstagramApiManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ScriptDevelop\InstagramApiManager\Contracts\WebhookProcessorInterface;
use ScriptDevelop\InstagramApiManager\Services\WebhookProcessors\BaseWebhookProcessor;
use Illuminate\Support\Facades\Log;

class InstagramWebhookController extends Controller
{
    protected WebhookProcessorInterface $processor;

    public function __construct()
    {
        try {
            $this->processor = app(WebhookProcessorInterface::class);
        } catch (\Exception $e) {
            $this->processor = new BaseWebhookProcessor(
                app(\ScriptDevelop\InstagramApiManager\Services\InstagramMessageService::class)
            );

            Log::channel('instagram')->warning(
                'WebhookProcessorInterface no pudo ser resuelto, usando implementación por defecto',
                ['error' => $e->getMessage()]
            );
        }
    }

    public function handle(Request $request)
    {
        try {
            return $this->processor->handle($request);
        } catch (\Exception $e) {
            Log::channel('instagram')->error('Error en el procesamiento del webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
