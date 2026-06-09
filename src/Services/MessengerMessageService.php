<?php

namespace ScriptDevelop\InstagramApiManager\Services;

use ScriptDevelop\InstagramApiManager\InstagramApi\ApiClient;
use ScriptDevelop\InstagramApiManager\Support\InstagramModelResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class MessengerMessageService
{
    protected ApiClient $apiClient;
    protected ?string $pageAccessToken = null;
    protected ?string $pageId = null;

    public function __construct()
    {
        $this->apiClient = app(ApiClient::class)
            ->withBaseUrl(config('facebook.api.base_url'))
            ->withVersion(config('facebook.api.version'));
    }

    public function withPageAccessToken(string $token): self
    {
        $this->pageAccessToken = $token;
        return $this;
    }

    public function withPageId(string $pageId): self
    {
        $this->pageId = $pageId;
        return $this;
    }

    protected function validateCredentials(): void
    {
        if (!$this->pageAccessToken || !$this->pageId) {
            throw new Exception('Page Access Token and Page ID must be set.');
        }
    }

    public function processWebhookMessage(array $messaging): array
    {
        Log::channel('facebook')->info('🔄 INICIANDO PROCESAMIENTO DE MENSAJE DE MESSENGER');
        try {
            return $this->processMessage($messaging);
        } catch (\Exception $e) {
            Log::channel('facebook')->error('❌ ERROR AL PROCESAR MENSAJE MESSENGER:', [
                'error' => $e->getMessage(),
                'messaging' => $messaging,
            ]);
            throw $e;
        }
    }

    protected function processMessage(array $messageData): array
    {
        Log::channel('facebook')->info('═══════════════════════════════════════════════════════');
        Log::channel('facebook')->info('🔄 INICIANDO PROCESAMIENTO DE MENSAJE MESSENGER');

        [$senderId, $recipientId, $isEcho] = $this->extractMessageContext($messageData);
        if (!$senderId || !$recipientId) {
            Log::channel('facebook')->warning('⚠️ Evento ignorado (sin sender/recipient)');
            return ['message' => null, 'conversation' => null];
        }

        $pageId = $isEcho ? $senderId : $recipientId;
        $contactUserId = $isEcho ? $recipientId : $senderId;

        Log::channel('facebook')->info('🔎 BUSCANDO PÁGINA DE FACEBOOK', [
            'page_id' => $pageId,
            'contact_id' => $contactUserId,
            'is_echo' => $isEcho,
        ]);

        $page = InstagramModelResolver::facebook_page()->where('page_id', $pageId)->first();
        if (!$page) {
            Log::channel('facebook')->error('❌ PÁGINA DE FACEBOOK NO ENCONTRADA', [
                'page_id' => $pageId,
                'hint' => 'Conecta la página primero via /facebook/connect',
            ]);
            return ['message' => null, 'conversation' => null];
        }

        Log::channel('facebook')->info('✅ Página encontrada', ['page_id' => $page->page_id]);

        $this->withPageAccessToken($page->access_token)->withPageId($page->page_id);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($messageData, $page, $contactUserId, $isEcho) {
            $conversation = $this->findOrCreateConversation($page->page_id, $contactUserId);
            $this->updateConversationStats($conversation, $isEcho);

            $eventResult = $this->handleEventByType($messageData, $conversation, $contactUserId, $page, $isEcho);

            if (!empty($eventResult['conversation']) && $eventResult['conversation'] instanceof Model) {
                $conversation = $eventResult['conversation'];
            }

            if ($this->shouldUpdateContact($messageData)) {
                $contact = $this->updateOrCreateContact($contactUserId, $page);

                if (empty($contact->name)) {
                    $nameFromPayload = $messageData['sender']['name'] ?? null;
                    if ($nameFromPayload) {
                        $contact->update(['name' => $nameFromPayload]);
                    }
                }
            }

            $this->dispatchBroadcastEvent($messageData, [
                'message' => $eventResult['message'] ?? null,
                'conversation' => $conversation,
            ]);

            Log::channel('facebook')->info('✅ PROCESAMIENTO COMPLETADO');
            Log::channel('facebook')->info('═══════════════════════════════════════════════════════');

            return [
                'message' => $eventResult['message'] ?? null,
                'conversation' => $conversation,
            ];
        });
    }

    protected function extractMessageContext(array $messageData): array
    {
        $isEcho = isset($messageData['message']['is_echo']) && $messageData['message']['is_echo'] === true;

        $senderId = $messageData['sender']['id'] ?? null;
        $recipientId = $messageData['recipient']['id'] ?? null;

        $senderId = is_array($senderId) ? ($senderId['id'] ?? (string) $senderId) : (string) $senderId;
        $recipientId = is_array($recipientId) ? ($recipientId['id'] ?? (string) $recipientId) : (string) $recipientId;

        return [$senderId, $recipientId, $isEcho];
    }

    public function findOrCreateConversation(string $pageId, string $messengerUserId): Model
    {
        $conversation = InstagramModelResolver::messenger_conversation()
            ->where('page_id', $pageId)
            ->where('messenger_user_id', $messengerUserId)
            ->whereNull('deleted_at')
            ->first();

        if ($conversation) {
            return $conversation;
        }

        return InstagramModelResolver::messenger_conversation()->create([
            'page_id' => $pageId,
            'messenger_user_id' => $messengerUserId,
            'last_message_at' => now(),
            'unread_count' => 0,
        ]);
    }

    protected function updateConversationStats(Model $conversation, bool $isEcho): void
    {
        $conversation->update([
            'last_message_at' => now(),
            'unread_count' => $isEcho ? $conversation->unread_count : $conversation->unread_count + 1,
        ]);
    }

    protected function handleEventByType(array $messageData, Model $conversation, string $contactUserId, Model $page, bool $isEcho): array
    {
        if (isset($messageData['message'])) {
            Log::channel('facebook')->info('→ Mensaje entrante');
            return [
                'message' => $this->processIncomingMessage($conversation, $messageData, $contactUserId, $page->page_id),
                'conversation' => null,
            ];
        }

        if (isset($messageData['postback'])) {
            Log::channel('facebook')->info('→ Postback');
            return [
                'message' => $this->processPostback($conversation, $messageData['postback'], $contactUserId, $page->page_id, $messageData['timestamp'] ?? null),
                'conversation' => null,
            ];
        }

        if (isset($messageData['reaction'])) {
            Log::channel('facebook')->info('→ Reacción');
            return [
                'message' => $this->processReaction($conversation, $messageData['reaction'], $contactUserId, $page->page_id),
                'conversation' => null,
            ];
        }

        if (isset($messageData['optin'])) {
            Log::channel('facebook')->info('→ Opt-in');
            $this->processOptin($conversation, $messageData['optin'], $contactUserId, $page->page_id);
            return ['message' => null, 'conversation' => $conversation];
        }

        if (isset($messageData['referral'])) {
            Log::channel('facebook')->info('→ Referral');
            return [
                'message' => null,
                'conversation' => $this->processReferral($conversation, $messageData['referral'], $contactUserId, $page->page_id) ?? $conversation,
            ];
        }

        if (isset($messageData['read'])) {
            Log::channel('facebook')->info('→ Evento de lectura');
            return [
                'message' => $this->processRead($conversation, $messageData, $contactUserId, $page->page_id),
                'conversation' => null,
            ];
        }

        if (isset($messageData['message_edit'])) {
            Log::channel('facebook')->info('→ Edición de mensaje');
            return [
                'message' => $this->processMessageEdit($conversation, $messageData['message_edit'], $contactUserId, $page->page_id),
                'conversation' => null,
            ];
        }

        Log::channel('facebook')->warning('⚠️ Tipo de evento desconocido', $messageData);
        return ['message' => null, 'conversation' => null];
    }

    protected function processIncomingMessage(Model $conversation, array $messageData, string $senderId, string $recipientId): ?Model
    {
        $message = $messageData['message'];
        $messageId = $message['mid'] ?? uniqid();

        $dbMessage = InstagramModelResolver::messenger_message()->where('message_id', $messageId)->first();

        if ($dbMessage) {
            $date = isset($messageData['timestamp']) ? date('Y-m-d H:i:s', $messageData['timestamp'] / 1000) : now();
            $status = 'delivered';

            if ($dbMessage->status === 'read') {
                $status = $dbMessage->status;
            }

            $msgHasAttachments = isset($message['attachments']) && is_array($message['attachments']) && count($message['attachments']) > 0;

            $updateData = [
                'status' => $status,
                'delivered_at' => $date,
                'message_content' => $message['text'] ?? $dbMessage->message_content,
                'json_content' => $message,
            ];

            if ($msgHasAttachments) {
                $updateData['attachments'] = $message['attachments'];
            }

            $dbMessage->update($updateData);

            if ($msgHasAttachments) {
                $this->processAttachments($message, $dbMessage);
            }

            Log::channel('facebook')->info('✅ Mensaje actualizado', ['message_id' => $messageId]);
            return $dbMessage;
        }

        $messageType = $this->determineMessageType($message);

        $messageDataInsert = [
            'conversation_id' => $conversation->id,
            'message_id' => $messageId,
            'message_method' => 'incoming',
            'message_type' => $messageType,
            'message_from' => $senderId,
            'message_to' => $recipientId,
            'message_content' => $message['text'] ?? null,
            'attachments' => $message['attachments'] ?? null,
            'json_content' => $message,
            'json' => $message,
            'status' => 'received',
            'created_time' => now(),
            'sent_at' => isset($message['timestamp']) ? date('Y-m-d H:i:s', $message['timestamp'] / 1000) : now(),
        ];

        if (isset($message['quick_reply'])) {
            $messageDataInsert['message_type'] = 'quick_reply';
            $messageDataInsert['message_context'] = 'quick_reply_response';
            $messageDataInsert['message_context_id'] = $message['quick_reply']['payload'] ?? null;
            $messageDataInsert['quick_reply_payload'] = $message['quick_reply']['payload'] ?? null;
            $messageDataInsert['context_message_text'] = $message['text'] ?? null;
        }

        if (isset($message['reply_to'])) {
            $messageDataInsert['message_context'] = 'reply';
            $messageDataInsert['message_context_id'] = $message['reply_to']['mid'] ?? null;
        }

        $savedMessage = InstagramModelResolver::messenger_message()->create($messageDataInsert);
        Log::channel('facebook')->info('✅ Mensaje guardado en BD', [
            'id' => $savedMessage->id,
            'message_id' => $savedMessage->message_id,
            'type' => $savedMessage->message_type,
        ]);

        $this->processAttachments($message, $savedMessage);

        return $savedMessage;
    }

    protected function determineMessageType(array $message): string
    {
        if (isset($message['quick_reply'])) {
            return 'quick_reply';
        }
        if (isset($message['attachments'])) {
            $attachment = $message['attachments'][0] ?? [];
            return $attachment['type'] ?? 'text';
        }
        if (isset($message['is_unsupported']) && $message['is_unsupported'] === true) {
            return 'unsupported';
        }
        return 'text';
    }

    protected function processAttachments(array $message, Model $savedMessage): void
    {
        if (!isset($message['attachments']) || !is_array($message['attachments'])) {
            return;
        }

        foreach ($message['attachments'] as $attachment) {
            if (isset($attachment['type'], $attachment['payload']['url'])) {
                $mediaUrl = (string) $attachment['payload']['url'];
                $mediaUrlHashBase = $this->urlWithoutQuery($mediaUrl);
                $mediaUrlHash = hash('sha256', $mediaUrlHashBase);

                $alreadyStored = InstagramModelResolver::messenger_media_message()
                    ->where('message_id', '=', $savedMessage->message_id)
                    ->where('media_type', '=', $attachment['type'])
                    ->where('media_url_hash', '=', $mediaUrlHash)
                    ->exists();
                Log::channel('facebook')->info('Revisar si existe registro', [
                        'message_id' => $savedMessage->message_id,
                        'type' => $attachment['type'],
                        'media_url' => $mediaUrl,
                    'media_url_hash_base' => $mediaUrlHashBase,
                        'alreadyStored' => $alreadyStored,
                    ]);

                if ($alreadyStored) {
                    Log::channel('facebook')->info('Adjunto duplicado detectado, se omite descarga/guardado', [
                        'message_id' => $savedMessage->message_id,
                        'type' => $attachment['type'],
                        'url' => $attachment['payload']['url'],
                    ]);
                    continue;
                }

                $mediaPath = $this->downloadMediaFile($attachment['payload']['url'], $attachment['type']);

                InstagramModelResolver::messenger_media_message()->create([
                    'message_id' => $savedMessage->message_id,
                    'media_type' => $attachment['type'],
                    'media_url' => $mediaUrl,
                    'media_url_hash' => $mediaUrlHash,
                    'local_path' => $mediaPath,
                    'json' => $attachment,
                ]);

                Log::channel('facebook')->info('📎 Adjunto procesado y guardado en messenger_media_messages', [
                    'type' => $attachment['type'],
                    'url' => $attachment['payload']['url'],
                ]);
            }
        }
    }

    protected function urlWithoutQuery(string $url): string
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return $url;
        }

        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '';

        return $scheme . $host . $port . $path;
    }

    protected function downloadMediaFile(string $url, string $type, ?string $filename = null): ?string
    {
        try {
            $disk = config('facebook.media.disk', 'public');
            $basePath = config('facebook.media.base_path', 'facebook');
            $maxSize = (int) config("facebook.media.max_file_size.{$type}", 8 * 1024 * 1024);

            $folderMap = ['image' => 'images', 'video' => 'videos', 'audio' => 'audios', 'file' => 'documents', 'user_profile_picture' => 'profile_pictures'];
            $subfolder = $folderMap[$type] ?? 'documents';

            $ext = $this->extractUrlExtension($url);
            if ($ext === null) {
                $ext = $type === 'audio' ? 'mp3' : ($type === 'video' ? 'mp4' : 'jpg');
            }
            $filename = ($filename ?? uniqid('msg_')) . '.' . $ext;
            $relativePath = "{$basePath}/{$subfolder}/{$filename}";
            $publicPath = '/storage/' . ltrim($relativePath, '/');

            $content = @file_get_contents($url);
            if ($content && strlen($content) <= $maxSize) {
                Storage::disk($disk)->put($relativePath, $content);

                Log::channel('facebook')->info('Archivo multimedia descargado y guardado', [
                    'url' => $url,
                    'path' => $publicPath,
                ]);

                return $publicPath;
            }
        } catch (Exception $e) {
            Log::channel('facebook')->warning('⚠️ No se pudo descargar archivo multimedia', [
                'url' => $url, 'error' => $e->getMessage(),
            ]);
        }
        return null;
    }

    /**
     * Extracts the file extension from a URL's path, ignoring query parameters.
     * Returns lowercase extension without dot, or null if none found.
     */
    protected function extractUrlExtension(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return null;
        }
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if ($ext === '' || strlen($ext) > 5) {
            return null;
        }
        return strtolower($ext);
    }

    // ------------------------------------------------------------------------
    // 6. Procesadores de eventos específicos
    // ------------------------------------------------------------------------
    protected function processPostback(Model $conversation, array $postback, string $senderId, string $recipientId, $timestamp = null): ?Model
    {
        $messageId = $postback['mid'] ?? 'postback_' . uniqid();
        if (InstagramModelResolver::messenger_message()->where('message_id', $messageId)->exists()) {
            Log::channel('facebook')->info('Postback duplicado ignorado', ['message_id' => $messageId]);
            return InstagramModelResolver::messenger_message()->where('message_id', $messageId)->first();
        }

        $savedMessage = InstagramModelResolver::messenger_message()->create([
            'conversation_id' => $conversation->id,
            'message_id' => $messageId,
            'message_method' => 'incoming',
            'message_type' => 'postback',
            'message_from' => $senderId,
            'message_to' => $recipientId,
            'message_content' => $postback['title'] ?? $postback['payload'] ?? null,
            'message_context' => 'button_postback',
            'message_context_id' => $postback['payload'] ?? null,
            'postback_payload' => $postback['payload'] ?? null,
            'context_message_text' => $postback['title'] ?? null,
            'json_content' => $postback,
            'status' => 'received',
            'created_time' => now(),
            'sent_at' => $timestamp ? date('Y-m-d H:i:s', $timestamp / 1000) : now(),
        ]);

        Log::channel('facebook')->info('Postback procesado', ['conversation_id' => $conversation->id]);
        return $savedMessage;
    }

    protected function processReaction(Model $conversation, array $reaction, string $senderId, string $recipientId): ?Model
    {
        $reactedMessage = InstagramModelResolver::messenger_message()
            ->where('message_id', $reaction['mid'] ?? '')
            ->first();

        if ($reactedMessage) {
            $currentReactions = $reactedMessage->reactions ?? [];
            $currentReactions[] = [
                'user_id' => $senderId,
                'reaction' => $reaction['reaction'] ?? 'like',
                'emoji' => $reaction['emoji'] ?? '❤️',
                'action' => $reaction['action'] ?? 'react',
                'timestamp' => now(),
            ];
            $reactedMessage->update(['reactions' => $currentReactions]);
        }

        Log::channel('facebook')->info('Reacción procesada', ['conversation_id' => $conversation->id]);
        return $reactedMessage;
    }

    protected function processOptin(Model $conversation, array $optin, string $senderId, string $recipientId): void
    {
        Log::channel('facebook')->info('Optin procesado', ['conversation_id' => $conversation->id]);
    }

    protected function processReferral(Model $conversation, array $referral, string $senderId, string $recipientId): ?Model
    {
        $ref = $referral['ref'] ?? null;
        $source = $referral['source'] ?? null;
        $type = $referral['type'] ?? null;

        $conversation->update([
            'last_referral' => $ref,
            'referral_source' => $source,
            'referral_type' => $type,
            'referral_timestamp' => now(),
        ]);

        InstagramModelResolver::messenger_referral()->create([
            'conversation_id' => $conversation->id,
            'messenger_user_id' => $senderId,
            'page_id' => $recipientId,
            'ref_parameter' => $ref,
            'source' => $source,
            'type' => $type,
            'processed_at' => now(),
        ]);

        Log::channel('facebook')->info('Referral procesado y guardado', [
            'conversation_id' => $conversation->id, 'ref' => $ref,
        ]);

        return $conversation;
    }

    protected function processRead(Model $conversation, array $messageData, string $senderId, string $recipientId): ?Model
    {
        $read = $messageData['read'] ?? null;
        $date = isset($messageData['timestamp']) ? date('Y-m-d H:i:s', $messageData['timestamp'] / 1000) : now();
        $updatedMessage = null;

        if (isset($read['watermark'])) {
            $watermarkTime = date('Y-m-d H:i:s', $read['watermark'] / 1000);

            InstagramModelResolver::messenger_message()
                ->where('conversation_id', $conversation->id)
                ->where('created_time', '<=', $watermarkTime)
                ->whereIn('status', ['sent', 'delivered'])
                ->update(['status' => 'read', 'read_at' => $date]);

            if (!isset($read['mid'])) {
                $updatedMessage = InstagramModelResolver::messenger_message()
                    ->where('conversation_id', $conversation->id)
                    ->where('created_time', '<=', $watermarkTime)
                    ->where('status', '=', 'read')
                    ->orderByDesc('created_time')
                    ->first();
            }
        }
        if (isset($read['mid'])) {
            $targetMessage = InstagramModelResolver::messenger_message()
                ->select('id', 'conversation_id', 'created_time')
                ->where('message_id', $read['mid'])
                ->first();

            if ($targetMessage) {
                InstagramModelResolver::messenger_message()
                    ->where('conversation_id', $targetMessage->conversation_id)
                    ->where('message_method', 'outgoing')
                    ->where('created_time', '<=', $targetMessage->created_time)
                    ->where(function ($query) {
                        $query->whereNull('read_at')
                            ->orWhereIn('status', ['sent', 'delivered']);
                    })
                    ->update(['status' => 'read', 'read_at' => $date]);

                $updatedMessage = InstagramModelResolver::messenger_message()
                    ->where('message_id', $read['mid'])
                    ->first();
            } else {
                // Fallback: si no existe el mensaje objetivo, se actualiza solo por MID.
                InstagramModelResolver::messenger_message()
                    ->where('message_id', $read['mid'])
                    ->whereNull('read_at')
                    ->update(['status' => 'read', 'read_at' => $date]);

                $updatedMessage = InstagramModelResolver::messenger_message()
                    ->where('message_id', $read['mid'])
                    ->first();
            }
        }

        Log::channel('facebook')->info('Read receipt procesado', ['conversation_id' => $conversation->id]);
        return $updatedMessage;
    }

    protected function processMessageEdit(Model $conversation, array $messageEdit, string $senderId, string $recipientId): ?Model
    {
        $mid = $messageEdit['mid'] ?? null;
        if (!$mid) {
            Log::channel('facebook')->warning('Edición de mensaje sin MID', $messageEdit);
            return null;
        }

        $message = InstagramModelResolver::messenger_message()->where('message_id', $mid)->first();
        if ($message) {
            $message->update([
                'message_content' => $messageEdit['text'] ?? $message->message_content,
                'json_content' => $messageEdit,
                'is_edited' => true,
                'edited_at' => now(),
            ]);
            Log::channel('facebook')->info('✅ Mensaje marcado como editado');
        }

        return $message;
    }

    protected function shouldUpdateContact(array $messageData): bool
    {
        return !isset($messageData['read'])
            && !isset($messageData['message_edit'])
            && !isset($messageData['reaction']);
    }

    protected function updateOrCreateContact(string $messengerUserId, Model $page): Model
    {
        $profileData = $this->fetchContactProfile($messengerUserId, $page);

        $data = [
            'page_id' => $page->page_id,
            'messenger_user_id' => $messengerUserId,
            'last_interaction_at' => now(),
        ];

        if (!empty($profileData)) {
            $data['name'] = $profileData['name'] ?? ($profileData['first_name'] ?? '') . ' ' . ($profileData['last_name'] ?? '');
            $data['name'] = trim($data['name']);
            $data['profile_picture'] = $profileData['profile_pic'] ?? null;
            $data['profile_synced_at'] = now();
        }

        $contact = InstagramModelResolver::messenger_contact()->updateOrCreate(
            ['page_id' => $page->page_id, 'messenger_user_id' => $messengerUserId],
            $data
        );

        if( config('facebook.media.download_user_profile_picture', false) && !empty($contact->profile_picture) ) {

            $mediaPath = $this->downloadMediaFile($contact->profile_picture, 'user_profile_picture', $messengerUserId . '.jpg');
            if ($mediaPath) {
                $contact->update(['local_profile_picture' => $mediaPath]);
                Log::channel('facebook')->info('Foto de perfil descargada y guardada', [
                    'user_id' => $messengerUserId,
                    'local_path' => $mediaPath,
                ]);
            }
        }

        Log::channel('facebook')->info('✅ Contacto actualizado', [
            'user_id' => $messengerUserId,
            'name' => $data['name'] ?? 'N/A',
        ]);

        return $contact;
    }

    protected function fetchContactProfile(string $messengerUserId, Model $page): array
    {
        $cacheEnabled = config('facebook.cache.contact_profile_enabled', true);
        $cacheKey = "facebook_contact_profile:{$messengerUserId}";
        $cachedTtl = (int) config('facebook.cache.contact_profile_ttl', 3600);

        if ($cacheEnabled && $cached = \Illuminate\Support\Facades\Cache::get($cacheKey)) {
            return $cached;
        }

        try {
            $token = $page->access_token;
            if (empty($token)) {
                Log::channel('facebook')->warning('🚫 Token vacío para la página', ['page_id' => $page->page_id]);
                return [];
            }

            $profileClient = app(ApiClient::class)
                ->withBaseUrl(config('facebook.api.base_url'))
                ->withVersion(config('facebook.api.version'));

            // Nota Cuau: La documentación https://developers.facebook.com/documentation/business-messaging/messenger-platform/identity/user-profile indica que:
            // profile_pic: La URL de la foto del perfil. La URL caducará.
            $response = $profileClient->request(
                'GET',
                $messengerUserId,
                [],
                null,
                [
                    'fields' => 'name,first_name,last_name,profile_pic,locale,timezone,gender',
                    'access_token' => $token,
                ]
            );

            if (is_array($response) && !isset($response['error'])) {
                if ($cacheEnabled) {
                    \Illuminate\Support\Facades\Cache::put($cacheKey, $response, $cachedTtl);
                }
                Log::channel('facebook')->info('✅ Perfil obtenido', [
                    'user_id' => $messengerUserId,
                    'name' => $response['name'] ?? null,
                ]);
                return $response;
            }

            $fallback = $this->fetchContactNameFromMessage($messengerUserId, $page);
            if ($fallback) return $fallback;

            Log::channel('facebook')->warning('⚠️ Perfil de contacto vacío (requiere Business Asset User Profile Access)');
            return [];

        } catch (Exception $e) {
            Log::channel('facebook')->warning('⚠️ No se pudo obtener perfil de contacto', [
                'user_id' => $messengerUserId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    protected function fetchContactNameFromMessage(string $messengerUserId, Model $page): array
    {
        try {
            $lastMessage = InstagramModelResolver::messenger_message()
                ->where('message_to', $page->page_id)
                ->where('message_from', $messengerUserId)
                ->orderByDesc('created_at')
                ->first();

            if ($lastMessage && $lastMessage->message_id) {
                $client = app(ApiClient::class)
                    ->withBaseUrl(config('facebook.api.base_url'))
                    ->withVersion(config('facebook.api.version'));

                $response = $client->request('GET', $lastMessage->message_id, [], null, [
                    'fields' => 'from',
                    'access_token' => $page->access_token,
                ]);

                $name = $response['from']['name'] ?? null;
                if ($name) {
                    Log::channel('facebook')->info('✅ Nombre obtenido vía fallback (message from)', ['name' => $name]);
                    return ['name' => $name];
                }
            }
        } catch (Exception $e) {
            Log::channel('facebook')->debug('Fallback de nombre no disponible');
        }
        return [];
    }

    protected function dispatchBroadcastEvent(array $messaging, array $processedData = []): void
    {
        $eventType = $this->resolveEventType($messaging);
        if (!$eventType) return;

        $eventClass = config("facebook.events.{$eventType}") ?? null;
        if (!$eventClass || !class_exists($eventClass)) return;

        $payload = [
            'sender' => $messaging['sender']['id'] ?? null,
            'recipient' => $messaging['recipient']['id'] ?? null,
            'timestamp' => $messaging['timestamp'] ?? null,
            'data' => $messaging[$eventType] ?? $messaging['message'] ?? $messaging,
            'message' => $processedData['message'] ?? null,
            'conversation' => $processedData['conversation'] ?? null,
        ];

        if (isset($processedData['delivery'])) {
            $payload['delivery'] = $processedData['delivery'];
        }
        if (isset($processedData['messages'])) {
            $payload['messages'] = $processedData['messages'];
        }

        try {
            event(new $eventClass($payload));
        } catch (Exception $e) {
            Log::channel('facebook')->error('Error al disparar evento broadcast', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function resolveEventType(array $messaging): ?string
    {
        if (isset($messaging['message'])) {
            return (isset($messaging['message']['is_echo']) && $messaging['message']['is_echo'] === true)
                ? 'message_echo'
                : 'message';
        }
        if (isset($messaging['postback'])) return 'postback';
        if (isset($messaging['reaction'])) return 'reaction';
        if (isset($messaging['optin'])) return 'optin';
        if (isset($messaging['referral'])) return 'referral';
        if (isset($messaging['read'])) return 'read';
        if (isset($messaging['message_edit'])) return 'message_edit';
        if (isset($messaging['delivery'])) return 'message_delivered';

        return null;
    }

    public function processDelivery(array $messaging): void
    {
        $delivery = $messaging['delivery'] ?? null;
        if (!$delivery) return;

        $pageId = $messaging['sender']['id'] ?? ($messaging['recipient']['id'] ?? null);
        if (!$pageId) return;

        $date = now();
        $updatedMessage = null;

        if (isset($delivery['watermark'])) {
            $watermarkDate = date('Y-m-d H:i:s', $delivery['watermark'] / 1000);
            InstagramModelResolver::messenger_message()
                ->where('message_from', $pageId)
                ->where('status', 'sent')
                ->where('sent_at', '<=', $watermarkDate)
                ->whereNull('delivered_at')
                ->update(['status' => 'delivered', 'delivered_at' => $date]);

            if( !isset($delivery['mids']) ) {
                $updatedMessage = InstagramModelResolver::messenger_message()
                    ->where('message_from', $pageId)
                    ->where('status', 'sent')
                    ->where('sent_at', '<=', $watermarkDate)
                    ->whereNotNull('delivered_at')
                    ->where('status', '=', 'delivered')
                    ->orderByDesc('created_time')
                    ->first();
            }

            Log::channel('facebook')->info('✅ Message delivery por watermark', [
                'page_id' => $pageId, 'watermark' => $delivery['watermark'],
            ]);
        }

        if (isset($delivery['mids']) && is_array($delivery['mids'])) {
            InstagramModelResolver::messenger_message()
                ->whereIn('message_id', $delivery['mids'])
                ->whereNull('delivered_at')
                ->update(['status' => 'delivered', 'delivered_at' => $date]);

            $updatedMessage = InstagramModelResolver::messenger_message()
                ->whereIn('message_id', $delivery['mids'])
                ->whereNotNull('delivered_at')
                ->where('status', '=', 'delivered')
                ->orderByDesc('created_time')
                ->first();

            Log::channel('facebook')->info('✅ Message delivery por mids', [
                'count' => count($delivery['mids']),
            ]);
        }

        if (config('facebook.broadcast.delivery_per_message', false) && isset($delivery['mids'])) {
            foreach ($delivery['mids'] as $mid) {
                $msg = InstagramModelResolver::messenger_message()->where('message_id', $mid)->first();
                $this->dispatchBroadcastEvent($messaging, [
                    'message' => $msg, 'conversation' => null, 'delivery' => $delivery,
                ]);
            }
        } else {
            $deliveredMessages = collect();
            if (isset($delivery['mids']) && is_array($delivery['mids'])) {
                $deliveredMessages = InstagramModelResolver::messenger_message()->whereIn('message_id', $delivery['mids'])->get();
            }
            $this->dispatchBroadcastEvent($messaging, [
                'message' => null, 'conversation' => null,
                'delivery' => $delivery, 'messages' => $deliveredMessages,
            ]);
        }
    }

    public function syncConversations(string $pageId, string $accessToken, int $limit = 100): ?array
    {
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $client = app(ApiClient::class)
                ->withBaseUrl(config('facebook.api.base_url'))
                ->withVersion(config('facebook.api.version'));

            $allConversations = [];
            $after = null;

            do {
                $query = [
                    'fields' => 'id,participants',
                    'access_token' => $accessToken,
                    'limit' => min($limit, 100),
                ];
                if ($after) $query['after'] = $after;

                $response = $client->request('GET', "{$pageId}/conversations", [], null, $query);

                foreach ($response['data'] ?? [] as $conv) {
                    $allConversations[] = $conv;
                    $participants = $conv['participants']['data'] ?? [];
                    foreach ($participants as $participant) {
                        if ($participant['id'] !== $pageId) {
                            InstagramModelResolver::messenger_conversation()->updateOrCreate(
                                ['conversation_id' => $conv['id']],
                                ['page_id' => $pageId, 'messenger_user_id' => $participant['id'], 'last_message_at' => now()]
                            );
                        }
                    }
                }

                $after = $response['paging']['cursors']['after'] ?? null;
            } while ($after && count($allConversations) < $limit);

            \Illuminate\Support\Facades\DB::commit();
            return ['data' => $allConversations, 'total' => count($allConversations)];
        } catch (Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::channel('facebook')->error('Error syncing conversations:', ['error' => $e->getMessage()]);
            return null;
        }
    }
}