<?php

namespace ScriptDevelop\InstagramApiManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ScriptDevelop\InstagramApiManager\Services\InstagramMessageService;
use Illuminate\Support\Facades\Log;

class InstagramWebhookController extends Controller
{
    protected InstagramMessageService $messageService;

    public function __construct(InstagramMessageService $messageService)
    {
        $this->messageService = $messageService;
    }


    public function handle(Request $request)
    {
        // Verificación del webhook (GET)
        if ($request->isMethod('get')) {
            return $this->handleVerification($request);
        }

        // Validar firma de webhook para solicitudes POST
        if ($request->isMethod('post') && !$this->validateWebhookSignature($request)) {
            Log::channel('instagram')->warning('Invalid webhook signature', [
                'signature' => $request->header('X-Hub-Signature-256'),
                'payload' => $request->getContent()
            ]);
            return response('Invalid signature', 403);
        }

        // Manejo de eventos (POST)
        if ($request->isMethod('post')) {
            return $this->handleEvent($request);
        }

        return response('Method Not Allowed', 405);
    }

    protected function handleVerification(Request $request)
    {
        $challenge = $request->get('hub_challenge');
        $verifyToken = $request->get('hub_verify_token');
        $expectedToken = config('instagram.webhook_verify_token');

        if ($verifyToken === $expectedToken && $challenge) {
            Log::info('Instagram webhook verified successfully');
            return response($challenge, 200);
        }

        Log::warning('Instagram webhook verification failed', [
            'expected_token' => $expectedToken,
            'received_token' => $verifyToken
        ]);

        return response('Forbidden', 403);
    }

    protected function validateWebhookSignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        
        if (!$signature) {
            Log::warning('Missing webhook signature');
            return false;
        }
        
        $expectedSignature = 'sha256=' . hash_hmac(
            'sha256', 
            $request->getContent(), 
            config('instagram.client_secret')
        );
        
        $isValid = hash_equals($expectedSignature, $signature);
        
        if (!$isValid) {
            Log::warning('Invalid webhook signature', [
                'expected' => $expectedSignature,
                'received' => $signature
            ]);
        }
        
        return $isValid;
    }

    protected function handleEvent(Request $request)
    {
        $data = $request->all();
        
        Log::channel('instagram')->info('Instagram Webhook event received:', $data);

        try {
            $this->messageService->processWebhookPayload($data);
            return response('EVENT_RECEIVED', 200);
        } catch (\Exception $e) {
            Log::channel('instagram')->error('Error processing Instagram webhook:', [
                'error' => $e->getMessage(),
                'payload' => $data
            ]);
            return response('ERROR_PROCESSING', 500);
        }
    }
}
