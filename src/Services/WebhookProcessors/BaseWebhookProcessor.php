<?php

namespace ScriptDevelop\InstagramApiManager\Services\WebhookProcessors;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use ScriptDevelop\InstagramApiManager\Contracts\WebhookProcessorInterface;
use ScriptDevelop\InstagramApiManager\Services\InstagramMessageService;
use ScriptDevelop\InstagramApiManager\Support\InstagramModelResolver;
use ScriptDevelop\InstagramApiManager\Traits\ValidatesHubSignature;

class BaseWebhookProcessor implements WebhookProcessorInterface
{
    use ValidatesHubSignature;
    protected InstagramMessageService $messageService;

    public function __construct(InstagramMessageService $messageService)
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

    public function processWebhookPayload(Request $request): JsonResponse
    {
        if (!$this->validateHubSignature($request, 'instagram')) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $data = $request->all();

        $correlationId = $request->header('X-Request-ID') ?? (string) \Illuminate\Support\Str::uuid();
        Log::channel('instagram')->info('=== WEBHOOK DE INSTAGRAM RECIBIDO ===', ['correlation_id' => $correlationId]);
        Log::channel('instagram')->info('Datos brutos del webhook:', $data);

        try {
            if (isset($data['entry']) && is_array($data['entry'])) {
                foreach ($data['entry'] as $entry) {
                    Log::channel('instagram')->info('Procesando entrada del webhook', [
                        'entry_id' => $entry['id'] ?? 'unknown',
                    ]);

                    if (isset($entry['messaging']) && is_array($entry['messaging'])) {
                        foreach ($entry['messaging'] as $messaging) {
                            Log::channel('instagram')->info('MENSAJE RECIBIDO EN EL WEBHOOK', [
                                'sender_id'    => $messaging['sender']['id'] ?? null,
                                'recipient_id' => $messaging['recipient']['id'] ?? null,
                                'timestamp'    => $messaging['timestamp'] ?? null,
                                'has_message'  => isset($messaging['message']),
                                'message_type' => $this->determineMessageType($messaging),
                            ]);

                            try {
                                $processedData = $this->messageService->processWebhookMessage($messaging);
                            } catch (\Exception $e) {
                                if ($this->isTokenError($e) && !empty($entry['id'])) {
                                    Log::channel('instagram')->warning('Error 190 detectado, intentando refrescar token...', [
                                        'entry_id' => $entry['id'],
                                    ]);
                                    $this->tryRefreshToken($entry['id'], 'instagram');
                                }
                                throw $e;
                            }
                        }
                    }

                    if (isset($entry['changes']) && is_array($entry['changes'])) {
                        foreach ($entry['changes'] as $change) {
                            $field = $change['field'] ?? null;
                            $value = $change['value'] ?? [];

                            if ($field === 'comments') {
                                $this->handleCommentChange($change);
                            } elseif ($field === 'mentions') {
                                $this->handleMentionChange($change);
                            }
                        }
                    }

                    if (!isset($entry['messaging']) && !isset($entry['changes'])) {
                        Log::channel('instagram')->warning('No hay mensajes ni cambios en esta entrada');
                    }
                }
            } else {
                Log::channel('instagram')->warning('Webhook sin entradas (entry)');
            }

            Log::channel('instagram')->info('=== WEBHOOK PROCESADO EXITOSAMENTE ===');
            return response()->json(['success' => true], 200);

        } catch (\Exception $e) {
            Log::channel('instagram')->error('ERROR PROCESANDO WEBHOOK:', [
                'error'   => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'payload' => $data,
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    protected function handleCommentChange(array $change): void
    {
        $value = $change['value'] ?? [];

        $commentId = $value['comment_id'] ?? null;
        $mediaId = $value['media']['id'] ?? null;
        $from = $value['from'] ?? [];
        $text = $value['text'] ?? null;
        $createdTime = isset($value['created_timestamp'])
            ? date('Y-m-d H:i:s', $value['created_timestamp'] / 1000)
            : now();

        Log::channel('instagram')->info('COMENTARIO RECIBIDO', [
            'comment_id' => $commentId,
            'from' => $from['username'] ?? null,
            'media_id' => $mediaId,
        ]);

        if ($commentId) {
            $this->saveCommentWebhook($commentId, $mediaId, $from, $text, $createdTime, $value);
        }

        event(new \ScriptDevelop\InstagramApiManager\Events\InstagramCommentReceived([
            'comment_id' => $commentId,
            'media_id' => $mediaId,
            'text' => $text,
            'from' => $from,
        ]));
    }

    protected function saveCommentWebhook(string $commentId, ?string $mediaId, array $from, ?string $text, string $createdTime, array $rawData): void
    {
        try {
            $existing = InstagramModelResolver::instagram_comment()
                ->where('comment_id', $commentId)
                ->first();

            if ($existing) {
                Log::channel('instagram')->debug('Comment already exists, skipping', ['comment_id' => $commentId]);
                return;
            }

            $businessAccountId = $this->resolveBusinessAccountIdFromMedia($mediaId);

            InstagramModelResolver::instagram_comment()->create([
                'comment_id' => $commentId,
                'instagram_business_account_id' => $businessAccountId,
                'instagram_media_id' => $mediaId,
                'instagram_user_id' => $from['id'] ?? null,
                'text' => $text,
                'username' => $from['username'] ?? null,
                'profile_picture_url' => $from['profile_picture_url'] ?? null,
                'created_time' => $createdTime,
                'message_type' => 'comment',
                'raw_data' => $rawData,
            ]);

            Log::channel('instagram')->info('Comment saved to database', ['comment_id' => $commentId]);
        } catch (\Exception $e) {
            Log::channel('instagram')->error('Error saving comment webhook', [
                'comment_id' => $commentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function handleMentionChange(array $change): void
    {
        $value = $change['value'] ?? [];

        $commentId = $value['comment_id'] ?? null;
        $mediaId = $value['media_id'] ?? null;
        $text = $value['text'] ?? null;
        $from = $value['from'] ?? [];
        $createdTime = isset($value['created_timestamp'])
            ? date('Y-m-d H:i:s', $value['created_timestamp'] / 1000)
            : now();

        Log::channel('instagram')->info('MENCION RECIBIDA', [
            'comment_id' => $commentId,
            'media_id' => $mediaId,
        ]);

        if ($commentId) {
            $this->saveMentionWebhook($commentId, $mediaId, $from, $text, $createdTime, $value);
        }

        event(new \ScriptDevelop\InstagramApiManager\Events\InstagramMentionReceived([
            'comment_id' => $commentId,
            'media_id' => $mediaId,
            'text' => $text,
            'from' => $from,
        ]));
    }

    protected function saveMentionWebhook(string $commentId, ?string $mediaId, array $from, ?string $text, string $createdTime, array $rawData): void
    {
        try {
            $existing = InstagramModelResolver::instagram_comment()
                ->where('comment_id', $commentId)
                ->first();

            if ($existing) {
                Log::channel('instagram')->debug('Mention comment already exists, skipping', ['comment_id' => $commentId]);
                return;
            }

            $businessAccountId = $this->resolveBusinessAccountIdFromMedia($mediaId);

            InstagramModelResolver::instagram_comment()->create([
                'comment_id' => $commentId,
                'instagram_business_account_id' => $businessAccountId,
                'instagram_media_id' => $mediaId,
                'instagram_user_id' => $from['id'] ?? null,
                'text' => $text,
                'username' => $from['username'] ?? null,
                'profile_picture_url' => $from['profile_picture_url'] ?? null,
                'created_time' => $createdTime,
                'message_type' => 'mention',
                'raw_data' => $rawData,
            ]);

            Log::channel('instagram')->info('Mention saved to database', ['comment_id' => $commentId]);
        } catch (\Exception $e) {
            Log::channel('instagram')->error('Error saving mention webhook', [
                'comment_id' => $commentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function resolveBusinessAccountIdFromMedia(?string $mediaId): ?string
    {
        if (!$mediaId) {
            return null;
        }

        try {
            $post = InstagramModelResolver::instagram_post()
                ->where('media_id', $mediaId)
                ->first();

            if ($post) {
                return $post->instagram_business_account_id;
            }

            $mediaPost = InstagramModelResolver::instagram_media_post()
                ->where('media_id', $mediaId)
                ->first();

            if ($mediaPost) {
                return $mediaPost->instagram_business_account_id;
            }
        } catch (\Exception $e) {
            Log::channel('instagram')->warning('Could not resolve business account ID from media', [
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

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

    protected function isTokenError(\Exception $e): bool
    {
        $message = strtolower($e->getMessage());
        return str_contains($message, 'error validating access token')
            || str_contains($message, 'token')
            || $e->getCode() === 190;
    }

    protected function tryRefreshToken(string $entryId, string $type): void
    {
        Log::channel('instagram')->info('Token refresh not implemented in webhook processor');
    }
}