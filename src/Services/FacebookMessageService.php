<?php

namespace ScriptDevelop\InstagramApiManager\Services;

use ScriptDevelop\InstagramApiManager\InstagramApi\ApiClient;
use ScriptDevelop\InstagramApiManager\Support\InstagramModelResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Exception;

class FacebookMessageService
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

    protected function isWithin24hWindow(Model $conversation): bool
    {
        if (!$conversation->last_message_at) return false;
        return $conversation->last_message_at->diffInHours(now()) < 24;
    }

    protected function findOrCreateConversation(string $pageId, string $messengerUserId): Model
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

    protected function sendMessageGeneric(
        string $recipientId,
        array $messagePayload,
        string $messageType,
        ?string $conversationId = null
    ): ?array {
        $this->validateCredentials();

        $conversation = $conversationId
            ? InstagramModelResolver::messenger_conversation()->find($conversationId)
            : $this->findOrCreateConversation($this->pageId, $recipientId);

        $isTagged = ($messagePayload['messaging_type'] ?? '') === 'MESSAGE_TAG';
        if (!$isTagged && !$this->isWithin24hWindow($conversation)) {
            Log::channel('facebook')->warning('⚠️ Envío fuera de ventana 24h sin MESSAGE_TAG', [
                'recipient' => $recipientId,
                'last_message_at' => $conversation->last_message_at,
            ]);
        }

        $payload = array_merge(['recipient' => ['id' => $recipientId]], $messagePayload);

        $messageData = [
            'conversation_id' => $conversation->id,
            'message_id' => 'temp_' . uniqid(),
            'message_method' => 'outgoing',
            'message_type' => $messageType,
            'message_from' => $this->pageId,
            'message_to' => $recipientId,
            'json_content' => $payload,
            'json' => $payload,
            'status' => 'pending',
            'created_time' => now(),
        ];

        if ($messageType === 'text') {
            $messageData['message_content'] = $messagePayload['message']['text'];
        } elseif (in_array($messageType, ['image', 'audio', 'video', 'file'])) {
            $messageData['message_content'] = $messagePayload['message']['attachment']['type'] ?? $messageType;
        } elseif ($messageType === 'sticker') {
            $messageData['message_content'] = 'sticker';
        } elseif ($messageType === 'generic_template') {
            $messageData['message_content'] = 'Generic Template';
        } elseif ($messageType === 'button_template') {
            $messageData['message_content'] = $messagePayload['message']['attachment']['payload']['text'] ?? null;
        }

        $message = InstagramModelResolver::messenger_message()->create($messageData);

        try {
            $response = $this->apiClient->request(
                'POST',
                $this->pageId . '/messages',
                [],
                $payload,
                ['access_token' => $this->pageAccessToken]
            );

            $message->update([
                'message_id' => $response['message_id'] ?? $response['id'] ?? uniqid(),
                'status' => 'sent',
                'sent_at' => now(),
                'json_content' => $response,
            ]);

            $conversation->update(['last_message_at' => now()]);

            return ['response' => $response, 'message' => $message, 'conversation' => $conversation];
        } catch (Exception $e) {
            $message->update(['status' => 'failed', 'failed_at' => now(), 'message_error' => $e->getMessage()]);
            Log::channel('facebook')->error("Error sending {$messageType}:", ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function sendTextMessage(string $recipientId, string $text, string $messagingType = 'RESPONSE', ?string $conversationId = null): ?array
    {
        return $this->sendMessageGeneric($recipientId, [
            'messaging_type' => $messagingType,
            'message' => ['text' => $text],
        ], 'text', $conversationId);
    }

    public function sendImageMessage(string $recipientId, string|\SplFileInfo $imageUrl, string $messagingType = 'RESPONSE', ?string $conversationId = null): ?array
    {
        $this->validateCredentials();
        $conversation = $this->findOrCreateConversation($this->pageId, $recipientId);
        $message = InstagramModelResolver::messenger_message()->create([
            'conversation_id' => $conversation->id, 'message_id' => 'temp_' . uniqid(),
            'message_method' => 'outgoing', 'message_type' => 'image',
            'message_from' => $this->pageId, 'message_to' => $recipientId,
            'message_content' => 'image', 'json_content' => ['type' => 'image'],
            'status' => 'pending', 'created_time' => now(),
        ]);
        try {
            $response = $this->apiClient->sendMediaRequest(
                $this->pageId . '/messages', ['id' => $recipientId], 'image', $imageUrl,
                ['messaging_type' => $messagingType], ['access_token' => $this->pageAccessToken], 'facebook'
            );
            $message->update(['message_id' => $response['message_id'] ?? uniqid(), 'status' => 'sent', 'sent_at' => now(), 'json_content' => $response]);
            $conversation->update(['last_message_at' => now()]);
            return ['response' => $response, 'message' => $message, 'conversation' => $conversation];
        } catch (Exception $e) {
            $message->update(['status' => 'failed', 'failed_at' => now(), 'message_error' => $e->getMessage()]);
            Log::channel('facebook')->error('Error sending image:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function sendAudioMessage(string $recipientId, string|\SplFileInfo $audioUrl, string $messagingType = 'RESPONSE', ?string $conversationId = null): ?array
    {
        $this->validateCredentials();
        $conversation = $this->findOrCreateConversation($this->pageId, $recipientId);
        $message = InstagramModelResolver::messenger_message()->create([
            'conversation_id' => $conversation->id, 'message_id' => 'temp_' . uniqid(),
            'message_method' => 'outgoing', 'message_type' => 'audio',
            'message_from' => $this->pageId, 'message_to' => $recipientId,
            'message_content' => 'audio', 'json_content' => ['type' => 'audio'],
            'status' => 'pending', 'created_time' => now(),
        ]);
        try {
            $response = $this->apiClient->sendMediaRequest(
                $this->pageId . '/messages', ['id' => $recipientId], 'audio', $audioUrl,
                ['messaging_type' => $messagingType], ['access_token' => $this->pageAccessToken], 'facebook'
            );
            $message->update(['message_id' => $response['message_id'] ?? uniqid(), 'status' => 'sent', 'sent_at' => now(), 'json_content' => $response]);
            $conversation->update(['last_message_at' => now()]);
            return ['response' => $response, 'message' => $message, 'conversation' => $conversation];
        } catch (Exception $e) {
            $message->update(['status' => 'failed', 'failed_at' => now(), 'message_error' => $e->getMessage()]);
            return null;
        }
    }

    public function sendVideoMessage(string $recipientId, string|\SplFileInfo $videoUrl, string $messagingType = 'RESPONSE', ?string $conversationId = null): ?array
    {
        $this->validateCredentials();
        $conversation = $this->findOrCreateConversation($this->pageId, $recipientId);
        $message = InstagramModelResolver::messenger_message()->create([
            'conversation_id' => $conversation->id, 'message_id' => 'temp_' . uniqid(),
            'message_method' => 'outgoing', 'message_type' => 'video',
            'message_from' => $this->pageId, 'message_to' => $recipientId,
            'message_content' => 'video', 'json_content' => ['type' => 'video'],
            'status' => 'pending', 'created_time' => now(),
        ]);
        try {
            $response = $this->apiClient->sendMediaRequest(
                $this->pageId . '/messages', ['id' => $recipientId], 'video', $videoUrl,
                ['messaging_type' => $messagingType], ['access_token' => $this->pageAccessToken], 'facebook'
            );
            $message->update(['message_id' => $response['message_id'] ?? uniqid(), 'status' => 'sent', 'sent_at' => now(), 'json_content' => $response]);
            $conversation->update(['last_message_at' => now()]);
            return ['response' => $response, 'message' => $message, 'conversation' => $conversation];
        } catch (Exception $e) {
            $message->update(['status' => 'failed', 'failed_at' => now(), 'message_error' => $e->getMessage()]);
            return null;
        }
    }

    public function sendFileMessage(string $recipientId, string|\SplFileInfo $fileUrl, string $messagingType = 'RESPONSE', ?string $conversationId = null): ?array
    {
        $this->validateCredentials();
        $conversation = $this->findOrCreateConversation($this->pageId, $recipientId);
        $message = InstagramModelResolver::messenger_message()->create([
            'conversation_id' => $conversation->id, 'message_id' => 'temp_' . uniqid(),
            'message_method' => 'outgoing', 'message_type' => 'file',
            'message_from' => $this->pageId, 'message_to' => $recipientId,
            'message_content' => 'file', 'json_content' => ['type' => 'file'],
            'status' => 'pending', 'created_time' => now(),
        ]);
        try {
            $response = $this->apiClient->sendMediaRequest(
                $this->pageId . '/messages', ['id' => $recipientId], 'file', $fileUrl,
                ['messaging_type' => $messagingType], ['access_token' => $this->pageAccessToken], 'facebook'
            );
            $message->update(['message_id' => $response['message_id'] ?? uniqid(), 'status' => 'sent', 'sent_at' => now(), 'json_content' => $response]);
            $conversation->update(['last_message_at' => now()]);
            return ['response' => $response, 'message' => $message, 'conversation' => $conversation];
        } catch (Exception $e) {
            $message->update(['status' => 'failed', 'failed_at' => now(), 'message_error' => $e->getMessage()]);
            return null;
        }
    }

    public function sendStickerMessage(string $recipientId, ?string $conversationId = null): ?array
    {
        return $this->sendMessageGeneric($recipientId, [
            'messaging_type' => 'RESPONSE',
            'message' => ['attachment' => ['type' => 'like_heart']],
        ], 'sticker', $conversationId);
    }

    public function sendQuickReplies(string $recipientId, string $text, array $quickReplies, string $messagingType = 'RESPONSE', ?string $conversationId = null): ?array
    {
        return $this->sendMessageGeneric($recipientId, [
            'messaging_type' => $messagingType,
            'message' => ['text' => $text, 'quick_replies' => $quickReplies],
        ], 'quick_reply', $conversationId);
    }

    public function sendGenericTemplate(string $recipientId, array $elements, string $messagingType = 'RESPONSE', ?string $conversationId = null): ?array
    {
        return $this->sendMessageGeneric($recipientId, [
            'messaging_type' => $messagingType,
            'message' => ['attachment' => ['type' => 'template', 'payload' => ['template_type' => 'generic', 'elements' => $elements]]],
        ], 'generic_template', $conversationId);
    }

    public function sendButtonTemplate(string $recipientId, string $text, array $buttons, string $messagingType = 'RESPONSE', ?string $conversationId = null): ?array
    {
        return $this->sendMessageGeneric($recipientId, [
            'messaging_type' => $messagingType,
            'message' => ['attachment' => ['type' => 'template', 'payload' => ['template_type' => 'button', 'text' => $text, 'buttons' => $buttons]]],
        ], 'button_template', $conversationId);
    }

    public function sendReadReceipt(string $recipientId): ?array
    {
        $this->validateCredentials();
        try {
            return $this->apiClient->request('POST', $this->pageId . '/messages', [], [
                'recipient' => ['id' => $recipientId],
                'sender_action' => 'mark_seen',
            ], ['access_token' => $this->pageAccessToken]);
        } catch (Exception $e) {
            Log::channel('facebook')->error('Error sending read receipt:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function sendReaction(string $recipientId, string $messageId, string $reaction = '❤️'): ?array
    {
        $this->validateCredentials();
        try {
            return $this->apiClient->request('POST', $this->pageId . '/messages', [], [
                'recipient' => ['id' => $recipientId],
                'sender_action' => 'react',
                'payload' => ['message_id' => $messageId],
                'reaction' => $reaction,
            ], ['access_token' => $this->pageAccessToken]);
        } catch (Exception $e) {
            Log::channel('facebook')->error('Error sending reaction:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function sendReply(string $recipientId, string $replyToMessageId, array $messagePayload, string $messagingType = 'RESPONSE', ?string $conversationId = null): ?array
    {
        $messagePayload['messaging_type'] = $messagePayload['messaging_type'] ?? $messagingType;
        $messagePayload['reply_to'] = ['mid' => $replyToMessageId];
        $msgType = $messagePayload['message']['attachment']['type'] ?? 'text';
        return $this->sendMessageGeneric($recipientId, $messagePayload, $msgType, $conversationId);
    }

    public function sendMultipleImages(string $recipientId, array $imageUrls, string $messagingType = 'RESPONSE', ?string $conversationId = null): ?array
    {
        $this->validateCredentials();
        $conversation = $this->findOrCreateConversation($this->pageId, $recipientId);
        $message = InstagramModelResolver::messenger_message()->create([
            'conversation_id' => $conversation->id, 'message_id' => 'temp_' . uniqid(),
            'message_method' => 'outgoing', 'message_type' => 'image',
            'message_from' => $this->pageId, 'message_to' => $recipientId,
            'message_content' => 'multiple images', 'json_content' => ['count' => count($imageUrls)],
            'status' => 'pending', 'created_time' => now(),
        ]);
        try {
            $response = $this->apiClient->sendMediaRequest(
                $this->pageId . '/messages', ['id' => $recipientId], 'image', array_slice($imageUrls, 0, 30),
                ['messaging_type' => $messagingType], ['access_token' => $this->pageAccessToken], 'facebook'
            );
            $message->update(['message_id' => $response['message_id'] ?? uniqid(), 'status' => 'sent', 'sent_at' => now(), 'json_content' => $response]);
            $conversation->update(['last_message_at' => now()]);
            return ['response' => $response, 'message' => $message, 'conversation' => $conversation];
        } catch (Exception $e) {
            $message->update(['status' => 'failed', 'failed_at' => now(), 'message_error' => $e->getMessage()]);
            return null;
        }
    }

    public function uploadAttachment(string $url, string $type = 'image'): ?string
    {
        $this->validateCredentials();
        try {
            $response = $this->apiClient->request('POST', $this->pageId . '/message_attachments', [], [
                'message' => json_encode([
                    'attachment' => ['type' => $type, 'payload' => ['url' => $url, 'is_reusable' => true]],
                ]),
            ], ['access_token' => $this->pageAccessToken]);
            return $response['attachment_id'] ?? null;
        } catch (Exception $e) {
            Log::channel('facebook')->error('Error uploading attachment:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function sendTaggedMessage(string $recipientId, string $tag, array $messagePayload): ?array
    {
        $this->validateCredentials();
        $messagePayload['messaging_type'] = 'MESSAGE_TAG';
        $messagePayload['tag'] = $tag;
        $msgType = $messagePayload['message']['attachment']['type'] ?? 'text';
        return $this->sendMessageGeneric($recipientId, $messagePayload, $msgType);
    }
}
