<?php

namespace ScriptDevelop\InstagramApiManager\Services\WebhookProcessors;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use ScriptDevelop\InstagramApiManager\Contracts\WebhookProcessorInterface;
use ScriptDevelop\InstagramApiManager\Services\MessengerMessageService;
use ScriptDevelop\InstagramApiManager\Traits\ValidatesHubSignature;
use ScriptDevelop\InstagramApiManager\Support\InstagramModelResolver;

class MessengerWebhookProcessor implements WebhookProcessorInterface
{
    use ValidatesHubSignature;
    protected MessengerMessageService $messageService;

    public function __construct(MessengerMessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    public function handle(Request $request): Response|JsonResponse
    {
        if ($request->isMethod('get')) {
            return $this->verifyWebhook($request);
        }
        if ($request->isMethod('post')) {
            return $this->processWebhookPayload($request);
        }
        return response('Method Not Allowed', 405);
    }

    public function verifyWebhook(Request $request): Response
    {
        $challenge = $request->get('hub_challenge');
        $verifyToken = $request->get('hub_verify_token');
        $expectedToken = config('facebook.webhook.verify_token');

        if ($verifyToken === $expectedToken && $challenge) {
            Log::channel('facebook')->info('Facebook webhook verified successfully');
            return response($challenge, 200);
        }

        Log::channel('facebook')->warning('Facebook webhook verification failed', [
            'expected_token' => $expectedToken,
            'received_token' => $verifyToken,
        ]);
        return response('Forbidden', 403);
    }

    public function processWebhookPayload(Request $request): JsonResponse
    {
        if (!$this->validateHubSignature($request, 'facebook')) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $data = $request->all();

        Log::channel('facebook')->info('=== WEBHOOK DE MESSENGER RECIBIDO ===');

        if ($data['object'] !== 'page') {
            Log::channel('facebook')->warning('Webhook no es de tipo page', ['object' => $data['object'] ?? 'unknown']);
            return response()->json(['success' => true], 200);
        }

        try {
            if (isset($data['entry']) && is_array($data['entry'])) {
                foreach ($data['entry'] as $entry) {
                    $pageId = $entry['id'] ?? null;
                    if (!$pageId) continue;

                    $page = InstagramModelResolver::facebook_page()->where('page_id', $pageId)->first();
                    if (!$page) {
                        Log::channel('facebook')->warning('Página no encontrada para entry', ['page_id' => $pageId]);
                        continue;
                    }

                    $this->messageService
                        ->withPageAccessToken($page->access_token)
                        ->withPageId($page->page_id);

                    if (isset($entry['messaging']) && is_array($entry['messaging'])) {
                        foreach ($entry['messaging'] as $messaging) {
                            if (isset($messaging['delivery'])) {
                                $this->messageService->processDelivery($messaging);
                            } else {
                                $this->messageService->processWebhookMessage($messaging);
                            }
                        }
                    }
                }
            }

            Log::channel('facebook')->info('=== WEBHOOK MESSENGER PROCESADO EXITOSAMENTE ===');
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            Log::channel('facebook')->error('❌ ERROR PROCESANDO WEBHOOK MESSENGER:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
