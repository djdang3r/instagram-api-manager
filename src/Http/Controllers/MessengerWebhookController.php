<?php

namespace ScriptDevelop\InstagramApiManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ScriptDevelop\InstagramApiManager\Services\MessengerMessageService;
use ScriptDevelop\InstagramApiManager\Services\WebhookProcessors\MessengerWebhookProcessor;

class MessengerWebhookController extends Controller
{
    public function handle(Request $request)
    {
        try {
            $processor = new MessengerWebhookProcessor(app(MessengerMessageService::class));
            return $processor->handle($request);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('facebook')->error('Error en controller Messenger:', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
