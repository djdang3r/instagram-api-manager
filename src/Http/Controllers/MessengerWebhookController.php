<?php

namespace ScriptDevelop\InstagramApiManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use ScriptDevelop\InstagramApiManager\Services\MessengerMessageService;
use ScriptDevelop\InstagramApiManager\Services\WebhookProcessors\MessengerWebhookProcessor;

class MessengerWebhookController extends Controller
{
    public function handle(Request $request)
    {
        if (config('facebook.webhook.async', false) && $request->isMethod('post')) {
            \ScriptDevelop\InstagramApiManager\Jobs\ProcessWebhookJob::dispatch(
                $request->all(), 'facebook', $request->header('X-Hub-Signature-256')
            );
            return response()->json(['success' => true], 200);
        }

        try {
            $processor = new MessengerWebhookProcessor(app(MessengerMessageService::class));
            return $processor->handle($request);
        } catch (\Exception $e) {
            Log::channel('facebook')->error('Error en controller Messenger:', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
