<?php

namespace ScriptDevelop\InstagramApiManager\Services;

use ScriptDevelop\InstagramApiManager\InstagramApi\ApiClient;
use Illuminate\Support\Facades\Log;
use Exception;
use ScriptDevelop\InstagramApiManager\Models\InstagramConversation;
use ScriptDevelop\InstagramApiManager\Models\InstagramMessage;
use ScriptDevelop\InstagramApiManager\Models\InstagramContact;

class InstagramMessageService
{
    protected ApiClient $apiClient;
    protected ?string $accessToken = null;
    protected ?string $instagramUserId = null;

    public function __construct()
    {
        $this->apiClient = new ApiClient(
            config('instagram.graph_base_url', 'https://graph.facebook.com'),
            config('instagram.api_version', 'v19.0'),
            (int) config('instagram.timeout', 30)
        );
    }

    /**
     * Establecer el token de acceso para las operaciones
     */
    public function withAccessToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    /**
     * Establecer el ID de usuario de Instagram para las operaciones
     */
    public function withInstagramUserId(string $instagramUserId): self
    {
        $this->instagramUserId = $instagramUserId;
        return $this;
    }

    public function processWebhookPayload(array $payload): void
    {
        try {
            foreach ($payload['entry'] ?? [] as $entry) {
                foreach ($entry['messaging'] ?? [] as $messaging) {
                    $this->processMessage($messaging);
                }
            }
        } catch (Exception $e) {
            Log::error('Error processing Instagram webhook:', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Generar un ID único para la conversación
     */
    protected function generateConversationId(string $senderId, string $recipientId): string
    {
        $ids = [$senderId, $recipientId];
        sort($ids);
        return 'instagram_conversation_' . md5(implode('_', $ids));
    }

    /**
     * Procesar postbacks (botones, quick replies, etc.)
     */
    protected function processPostback(InstagramConversation $conversation, array $postback, string $senderId, string $recipientId): void
    {
        InstagramMessage::create([
            'conversation_id' => $conversation->id,
            'message_id' => 'postback_' . ($postback['mid'] ?? uniqid()),
            'message_method' => 'incoming',
            'message_type' => 'postback',
            'message_from' => ['id' => $senderId],
            'message_to' => ['id' => $recipientId],
            'message_content' => $postback['title'] ?? $postback['payload'] ?? null,
            'json_content' => $postback,
            'status' => 'received',
            'created_time' => now(),
            'sent_at' => isset($postback['timestamp']) ? 
                date('Y-m-d H:i:s', $postback['timestamp'] / 1000) : 
                now()
        ]);
        
        Log::info('Instagram postback processed', [
            'conversation_id' => $conversation->id,
            'postback' => $postback
        ]);
    }

    /**
     * Procesar reacciones a mensajes
     */
    protected function processReaction(InstagramConversation $conversation, array $reaction, string $senderId, string $recipientId): void
    {
        $reactedMessage = InstagramMessage::where('message_id', $reaction['mid'] ?? '')->first();
        
        if ($reactedMessage) {
            $currentReactions = $reactedMessage->reactions ?? [];
            $currentReactions[] = [
                'user_id' => $senderId,
                'reaction' => $reaction['reaction'] ?? 'like',
                'emoji' => $reaction['emoji'] ?? '❤️',
                'action' => $reaction['action'] ?? 'react',
                'timestamp' => now()
            ];
            
            $reactedMessage->update([
                'reactions' => $currentReactions
            ]);
        }
        
        Log::info('Instagram reaction processed', [
            'conversation_id' => $conversation->id,
            'reaction' => $reaction
        ]);
    }

    protected function processMessage(array $messageData): void
    {
        $senderId = $messageData['sender']['id'] ?? null;
        $recipientId = $messageData['recipient']['id'] ?? null;
        
        if (!$senderId || !$recipientId) {
            Log::warning('Invalid message data: missing sender or recipient', $messageData);
            return;
        }

        try {
            $conversation = $this->findOrCreateConversation($recipientId, $senderId);

            $conversation->update([
                'last_message_at' => now(),
                'updated_time' => now(),
                'unread_count' => $conversation->unread_count + 1
            ]);

            if (isset($messageData['message'])) {
                $this->processIncomingMessage($conversation, $messageData['message'], $senderId, $recipientId);
            } elseif (isset($messageData['postback'])) {
                $this->processPostback($conversation, $messageData['postback'], $senderId, $recipientId);
            } elseif (isset($messageData['reaction'])) {
                $this->processReaction($conversation, $messageData['reaction'], $senderId, $recipientId);
            } elseif (isset($messageData['optin'])) {
                $this->processOptin($conversation, $messageData['optin'], $senderId, $recipientId);
            } elseif (isset($messageData['referral'])) {
                $this->processReferral($conversation, $messageData['referral'], $senderId, $recipientId);
            } elseif (isset($messageData['read'])) {
                $this->processRead($conversation, $messageData['read'], $senderId, $recipientId);
            } else {
                Log::warning('Unknown message type received', $messageData);
            }

            $this->updateContact($senderId, $messageData);
            
        } catch (Exception $e) {
            Log::error('Error processing Instagram message:', [
                'error' => $e->getMessage(),
                'message_data' => $messageData
            ]);
        }
    }

    /**
     * Procesar mensajes entrantes de cualquier tipo
     */
    protected function processIncomingMessage(InstagramConversation $conversation, array $message, string $senderId, string $recipientId): void
    {
        $messageType = $this->determineMessageType($message);
        $messageData = [
            'conversation_id' => $conversation->id,
            'message_id' => $message['mid'] ?? uniqid(),
            'message_method' => 'incoming',
            'message_type' => $messageType,
            'message_from' => ['id' => $senderId],
            'message_to' => ['id' => $recipientId],
            'message_content' => $message['text'] ?? null,
            'attachments' => $message['attachments'] ?? null,
            'json_content' => $message,
            'status' => 'received',
            'created_time' => now(),
            'sent_at' => isset($message['timestamp']) ? 
                date('Y-m-d H:i:s', $message['timestamp'] / 1000) : 
                now()
        ];

        // Procesar adjuntos si existen
        if (isset($message['attachments']) && is_array($message['attachments'])) {
            foreach ($message['attachments'] as $attachment) {
                if (isset($attachment['type']) && isset($attachment['payload']['url'])) {
                    if ($attachment['type'] === 'image' || $attachment['type'] === 'video' || $attachment['type'] === 'audio') {
                        $messageData['media_url'] = $attachment['payload']['url'];
                    }
                }
            }
        }

        InstagramMessage::create($messageData);
    }
    
    protected function processOptin(InstagramConversation $conversation, array $optin, string $senderId, string $recipientId): void
    {
        Log::info('Instagram optin processed', [
            'conversation_id' => $conversation->id,
            'optin' => $optin
        ]);
    }

    protected function processReferral(InstagramConversation $conversation, array $referral, string $senderId, string $recipientId): void
    {
        Log::info('Instagram referral processed', [
            'conversation_id' => $conversation->id,
            'referral' => $referral
        ]);
    }

    protected function processRead(InstagramConversation $conversation, array $read, string $senderId, string $recipientId): void
    {
        if (isset($read['watermark'])) {
            InstagramMessage::where('conversation_id', $conversation->id)
                ->where('created_time', '<=', date('Y-m-d H:i:s', $read['watermark'] / 1000))
                ->where('status', 'sent')
                ->update(['status' => 'read', 'read_at' => now()]);
        }
        
        Log::info('Instagram read receipt processed', [
            'conversation_id' => $conversation->id,
            'read' => $read
        ]);
    }

    protected function determineMessageType(array $message): string
    {
        if (isset($message['attachments'])) {
            $attachment = $message['attachments'][0] ?? [];
            return $attachment['type'] ?? 'text';
        }
        return 'text';
    }

    protected function updateContact(string $instagramUserId, array $messageData): void
    {
        $profile = $messageData['sender']['profile'] ?? [];
        
        InstagramContact::updateOrCreate(
            ['instagram_user_id' => $instagramUserId],
            [
                'username' => $profile['username'] ?? null,
                'profile_picture' => $profile['profile_pic'] ?? null,
                'name' => $profile['name'] ?? null,
            ]
        );
    }

    public function markAsRead(string $messageId): bool
    {
        try {
            $message = InstagramMessage::where('message_id', $messageId)->first();
            if ($message) {
                $message->update([
                    'status' => 'read',
                    'read_at' => now()
                ]);
                return true;
            }
            return false;
        } catch (Exception $e) {
            Log::error('Error marking message as read:', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Método genérico para enviar mensajes y manejar el estado en la base de datos
     */
    protected function sendMessageGeneric(string $recipientId, array $payload, string $messageType, string $conversationId = null, ?string $mediaUrl = null, ?string $postId = null): ?array
    {
        $this->validateCredentials();

        $conversation = $conversationId ? 
            InstagramConversation::find($conversationId) :
            $this->findOrCreateConversation($this->instagramUserId, $recipientId);

        $messageData = [
            'conversation_id' => $conversation->id,
            'message_id' => 'temp_' . uniqid(),
            'message_method' => 'outgoing',
            'message_type' => $messageType,
            'message_from' => ['id' => $this->instagramUserId],
            'message_to' => ['id' => $recipientId],
            'status' => 'pending',
            'created_time' => now(),
        ];

        if ($messageType === 'text') {
            $messageData['message_content'] = $payload['message']['text'];
        } elseif (in_array($messageType, ['image', 'audio', 'video'])) {
            $messageData['media_url'] = $mediaUrl;
            $messageData['message_content'] = $payload['message']['attachment']['type'];
        } elseif ($messageType === 'sticker') {
            $messageData['message_content'] = 'sticker';
        } elseif ($messageType === 'post') {
            $messageData['message_context_id'] = $postId;
            $messageData['message_content'] = 'shared_post';
        } elseif ($messageType === 'reaction') {
            $messageData['message_content'] = $payload['payload']['reaction'] ?? 'reaction';
        }

        $message = InstagramMessage::create($messageData);

        try {
            $response = $this->apiClient->request(
                'POST',
                $this->instagramUserId . '/messages',
                [],
                $payload,
                [
                    'access_token' => $this->accessToken
                ]
            );

            $message->update([
                'message_id' => $response['message_id'] ?? $response['id'] ?? uniqid(),
                'status' => 'sent',
                'sent_at' => now(),
                'json_content' => $response
            ]);

            $conversation->update([
                'last_message_at' => now(),
                'updated_time' => now()
            ]);

            return $response;
        } catch (Exception $e) {
            $message->update([
                'status' => 'failed',
                'failed_at' => now(),
                'message_error' => $e->getMessage()
            ]);

            Log::error("Error enviando mensaje de {$messageType}:", [
                'error' => $e->getMessage(),
                'recipient_id' => $recipientId
            ]);
            return null;
        }
    }

    /**
     * Enviar un mensaje de texto
     */
    public function sendTextMessage(string $recipientId, string $text, string $conversationId = null): ?array
    {
        $payload = [
            'recipient' => [
                'id' => $recipientId
            ],
            'message' => [
                'text' => $text
            ]
        ];

        return $this->sendMessageGeneric($recipientId, $payload, 'text', $conversationId);
    }

    /**
     * Enviar una imagen o GIF
     */
    public function sendImageMessage(string $recipientId, string $imageUrl, string $conversationId = null): ?array
    {
        $payload = [
            'recipient' => [
                'id' => $recipientId
            ],
            'message' => [
                'attachment' => [
                    'type' => 'image',
                    'payload' => [
                        'url' => $imageUrl
                    ]
                ]
            ]
        ];

        return $this->sendMessageGeneric($recipientId, $payload, 'image', $conversationId, $imageUrl);
    }

    /**
     * Enviar un mensaje de audio
     */
    public function sendAudioMessage(string $recipientId, string $audioUrl, string $conversationId = null): ?array
    {
        $payload = [
            'recipient' => [
                'id' => $recipientId
            ],
            'message' => [
                'attachment' => [
                    'type' => 'audio',
                    'payload' => [
                        'url' => $audioUrl
                    ]
                ]
            ]
        ];

        return $this->sendMessageGeneric($recipientId, $payload, 'audio', $conversationId, $audioUrl);
    }

    /**
     * Enviar un mensaje de video
     */
    public function sendVideoMessage(string $recipientId, string $videoUrl, string $conversationId = null): ?array
    {
        $payload = [
            'recipient' => [
                'id' => $recipientId
            ],
            'message' => [
                'attachment' => [
                    'type' => 'video',
                    'payload' => [
                        'url' => $videoUrl
                    ]
                ]
            ]
        ];

        return $this->sendMessageGeneric($recipientId, $payload, 'video', $conversationId, $videoUrl);
    }

    /**
     * Enviar un sticker (corazón)
     */
    public function sendStickerMessage(string $recipientId, string $conversationId = null): ?array
    {
        $payload = [
            'recipient' => [
                'id' => $recipientId
            ],
            'message' => [
                'attachment' => [
                    'type' => 'like_heart'
                ]
            ]
        ];

        return $this->sendMessageGeneric($recipientId, $payload, 'sticker', $conversationId);
    }

    /**
     * Reaccionar a un mensaje
     */
    public function reactToMessage(string $recipientId, string $messageId, string $reaction = 'love', string $conversationId = null): ?array
    {
        $payload = [
            'recipient' => [
                'id' => $recipientId
            ],
            'sender_action' => 'react',
            'payload' => [
                'message_id' => $messageId,
                'reaction' => $reaction
            ]
        ];

        return $this->sendMessageGeneric($recipientId, $payload, 'reaction', $conversationId);
    }

    /**
     * Eliminar reacción de un mensaje
     */
    public function unreactToMessage(string $recipientId, string $messageId, string $conversationId = null): ?array
    {
        $payload = [
            'recipient' => [
                'id' => $recipientId
            ],
            'sender_action' => 'unreact',
            'payload' => [
                'message_id' => $messageId
            ]
        ];

        return $this->sendMessageGeneric($recipientId, $payload, 'reaction', $conversationId);
    }

    /**
     * Enviar un post publicado
     */
    public function sendPublishedPost(string $recipientId, string $postId, string $conversationId = null): ?array
    {
        if (!$this->verifyPostOwnership($postId)) {
            throw new Exception("El usuario no es propietario de este post");
        }

        $payload = [
            'recipient' => [
                'id' => $recipientId
            ],
            'message' => [
                'attachment' => [
                    'type' => 'MEDIA_SHARE',
                    'payload' => [
                        'id' => $postId
                    ]
                ]
            ]
        ];

        return $this->sendMessageGeneric($recipientId, $payload, 'post', $conversationId, null, $postId);
    }

    /**
     * Enviar un mensaje fuera de la ventana de 24 horas usando una etiqueta
     */
    public function sendMessageWithTag(string $recipientId, string $text, string $tag, string $conversationId = null): ?array
    {
        $allowedTags = ['ISSUE_RESOLUTION', 'APPOINTMENT_UPDATE', 'SHIPPING_UPDATE', 'RESERVATION_UPDATE', 'GAME_EVENT', 'TRANSPORTATION_UPDATE', 'FEATURE_FUNCTIONALITY_UPDATE', 'TICKET_UPDATE'];
        
        if (!in_array($tag, $allowedTags)) {
            throw new Exception("Etiqueta no permitida: $tag");
        }

        $payload = [
            'recipient' => [
                'id' => $recipientId
            ],
            'message' => [
                'text' => $text
            ],
            'tag' => $tag
        ];

        return $this->sendMessageGeneric($recipientId, $payload, 'text', $conversationId);
    }

    /**
     * Verificar si el usuario es propietario de un post
     */
    public function verifyPostOwnership(string $postId): bool
    {
        $this->validateCredentials();

        try {
            $post = $this->apiClient->request(
                'GET',
                $postId,
                [],
                null,
                [
                    'access_token' => $this->accessToken,
                    'fields' => 'id,owner'
                ]
            );

            return isset($post['owner']['id']) && $post['owner']['id'] === $this->instagramUserId;
        } catch (Exception $e) {
            Log::error('Error verificando propiedad del post:', [
                'error' => $e->getMessage(),
                'post_id' => $postId
            ]);
            return false;
        }
    }

    /**
     * Validar que las credenciales estén establecidas
     */
    protected function validateCredentials(): void
    {
        if (!$this->accessToken) {
            throw new Exception('Access token is required. Use withAccessToken() method first.');
        }

        if (!$this->instagramUserId) {
            throw new Exception('Instagram user ID is required. Use withInstagramUserId() method first.');
        }
    }

    /**
     * Obtener conversaciones
     */
    public function getConversations(): ?array
    {
        $this->validateCredentials();

        try {
            return $this->apiClient->request(
                'GET',
                $this->instagramUserId . '/conversations',
                [],
                null,
                [
                    'access_token' => $this->accessToken,
                    'fields' => 'id,senders,updated_time,messages{id,from,to,message}'
                ]
            );
        } catch (Exception $e) {
            Log::error('Error obteniendo conversaciones:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Obtener mensajes de una conversación
     */
    public function getMessages(string $conversationId): ?array
    {
        $this->validateCredentials();

        try {
            return $this->apiClient->request(
                'GET',
                $conversationId,
                [],
                null,
                [
                    'access_token' => $this->accessToken,
                    'fields' => 'messages{id,from,to,message,attachments,created_time}'
                ]
            );
        } catch (Exception $e) {
            Log::error('Error obteniendo mensajes:', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversationId
            ]);
            return null;
        }
    }

    /**
     * Obtener o crear una conversación
     */
    public function findOrCreateConversation(string $instagramAccountId, string $userId): InstagramConversation
    {
        return InstagramConversation::firstOrCreate(
            [
                'instagram_business_account_id' => $instagramAccountId,
                'instagram_user_id' => $userId
            ],
            [
                'conversation_id' => $this->generateConversationId($userId, $instagramAccountId),
                'senders' => [$userId, $instagramAccountId]
            ]
        );
    }

    /**
     * Sincronizar conversaciones desde la API de Instagram
     */
    public function syncConversations(string $accessToken, string $instagramUserId): void
    {
        $this->validateCredentials();
        
        try {
            $conversations = $this->getConversations();
            
            foreach ($conversations['data'] ?? [] as $conversationData) {
                $conversation = InstagramConversation::updateOrCreate(
                    ['conversation_id' => $conversationData['id']],
                    [
                        'instagram_business_account_id' => $instagramUserId,
                        'senders' => $conversationData['senders']['data'] ?? [],
                        'updated_time' => isset($conversationData['updated_time']) ? 
                            date('Y-m-d H:i:s', strtotime($conversationData['updated_time'])) : 
                            null,
                        'unread_count' => $conversationData['unread_count'] ?? 0,
                        'is_archived' => $conversationData['is_archived'] ?? false
                    ]
                );
                
                $this->syncConversationMessages($conversation, $accessToken);
            }
        } catch (Exception $e) {
            Log::error('Error syncing Instagram conversations:', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Sincronizar mensajes de una conversación específica
     */
    protected function syncConversationMessages(InstagramConversation $conversation, string $accessToken): void
    {
        try {
            $messages = $this->getMessages($conversation->conversation_id);
            
            foreach ($messages['data'] ?? [] as $messageData) {
                InstagramMessage::updateOrCreate(
                    ['message_id' => $messageData['id']],
                    [
                        'conversation_id' => $conversation->id,
                        'message_method' => $this->determineMessageMethod($messageData, $conversation->instagram_business_account_id),
                        'message_type' => $this->determineMessageTypeFromApi($messageData),
                        'message_from' => $messageData['from'] ?? [],
                        'message_to' => $messageData['to']['data'] ?? [],
                        'message_content' => $messageData['message'] ?? null,
                        'attachments' => $messageData['attachments']['data'] ?? [],
                        'status' => 'received',
                        'created_time' => isset($messageData['created_time']) ? 
                            date('Y-m-d H:i:s', strtotime($messageData['created_time'])) : 
                            null,
                        'json_content' => $messageData
                    ]
                );
            }
            
            if (!empty($messages['data'])) {
                $lastMessage = end($messages['data']);
                $conversation->update([
                    'last_message_at' => isset($lastMessage['created_time']) ? 
                        date('Y-m-d H:i:s', strtotime($lastMessage['created_time'])) : 
                        now()
                ]);
            }
        } catch (Exception $e) {
            Log::error('Error syncing conversation messages:', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversation->id
            ]);
        }
    }

    /**
     * Determinar el método del mensaje (entrante/saliente)
     */
    protected function determineMessageMethod(array $messageData, string $businessAccountId): string
    {
        $senderId = $messageData['from']['id'] ?? null;
        return $senderId === $businessAccountId ? 'outgoing' : 'incoming';
    }

    /**
     * Determinar el tipo de mensaje desde la API
     */
    protected function determineMessageTypeFromApi(array $messageData): string
    {
        if (!empty($messageData['attachments']['data'])) {
            $attachment = $messageData['attachments']['data'][0];
            return $attachment['type'] ?? 'text';
        }
        
        return 'text';
    }
}