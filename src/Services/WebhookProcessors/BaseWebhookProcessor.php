<?php

namespace ScriptDevelop\InstagramApiManager\Services\WebhookProcessors;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use ScriptDevelop\InstagramApiManager\Contracts\WebhookProcessorInterface;
use ScriptDevelop\InstagramApiManager\Services\InstagramMessageService;
use ScriptDevelop\InstagramApiManager\Traits\ValidatesHubSignature;

class BaseWebhookProcessor implements WebhookProcessorInterface
{
    use ValidatesHubSignature;
    protected InstagramMessageService $messageService;

    public function __construct(InstagramMessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * Maneja la solicitud del webhook: GET para verificación, POST para procesamiento.
     */
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

    /**
     * Verificación del webhook (GET) — Meta envía hub.mode, hub.challenge, hub.verify_token.
     */
    public function verifyWebhook(Request $request): Response
    {
        $challenge = $request->get('hub_challenge');
        $verifyToken = $request->get('hub_verify_token');
        $expectedToken = config('instagram.webhook.verify_token');

        if ($verifyToken === $expectedToken && $challenge) {
            Log::channel('instagram')->info('Instagram webhook verified successfully');
            return response($challenge, 200);
        }

        Log::channel('instagram')->warning('Instagram webhook verification failed', [
            'expected_token' => $expectedToken,
            'received_token' => $verifyToken,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Procesa el payload del webhook (POST).
     * Itera entries → messaging, delega al InstagramMessageService, y dispara eventos broadcast.
     */
    public function processWebhookPayload(Request $request): JsonResponse
    {
        if (!$this->validateHubSignature($request, 'instagram')) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $data = $request->all();

        Log::channel('instagram')->info('=== WEBHOOK DE INSTAGRAM RECIBIDO ===');
        Log::channel('instagram')->info('Datos brutos del webhook:', $data);

        try {
            if (isset($data['entry']) && is_array($data['entry'])) {
                foreach ($data['entry'] as $entry) {
                    Log::channel('instagram')->info('Procesando entrada del webhook', [
                        'entry_id' => $entry['id'] ?? 'unknown',
                    ]);

                    if (isset($entry['messaging']) && is_array($entry['messaging'])) {
                        foreach ($entry['messaging'] as $messaging) {
                            Log::channel('instagram')->info('📨 MENSAJE RECIBIDO EN EL WEBHOOK', [
                                'sender_id'    => $messaging['sender']['id'] ?? null,
                                'recipient_id' => $messaging['recipient']['id'] ?? null,
                                'timestamp'    => $messaging['timestamp'] ?? null,
                                'has_message'  => isset($messaging['message']),
                                'message_type' => $this->determineMessageType($messaging),
                            ]);

                            // Procesar y almacenar el mensaje usando el servicio existente
                            $processedData = $this->messageService->processWebhookMessage($messaging);
                        }
                    } else {
                        Log::channel('instagram')->warning('No hay mensajes en esta entrada del webhook');
                    }
                }
            } else {
                Log::channel('instagram')->warning('Webhook sin entradas (entry)');
            }

            Log::channel('instagram')->info('=== WEBHOOK PROCESADO EXITOSAMENTE ===');
            return response()->json(['success' => true], 200);

        } catch (\Exception $e) {
            Log::channel('instagram')->error('❌ ERROR PROCESANDO WEBHOOK:', [
                'error'   => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'payload' => $data,
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Determinar el tipo de mensaje para logging y despacho de eventos.
     */
    protected function determineMessageType(array $messaging): string
    {
        if (isset($messaging['message'])) {
            if (isset($messaging['message']['text'])) {
                return 'text_message';
            } elseif (isset($messaging['message']['attachments'])) {
                return 'attachment_message';
            }
            return 'message';
        } elseif (isset($messaging['postback'])) {
            return 'postback';
        } elseif (isset($messaging['reaction'])) {
            return 'reaction';
        } elseif (isset($messaging['read'])) {
            return 'read_event';
        } elseif (isset($messaging['referral'])) {
            return 'referral';
        } elseif (isset($messaging['optin'])) {
            return 'optin';
        }
        return 'unknown';
    }

}
