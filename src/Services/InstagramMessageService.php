<?php

namespace ScriptDevelop\InstagramApiManager\Services;

use ScriptDevelop\InstagramApiManager\InstagramApi\ApiClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Exception;
use ScriptDevelop\InstagramApiManager\Models\InstagramConversation;
use ScriptDevelop\InstagramApiManager\Models\InstagramMessage;
use ScriptDevelop\InstagramApiManager\Models\InstagramContact;
use ScriptDevelop\InstagramApiManager\Models\InstagramBusinessAccount;
use ScriptDevelop\InstagramApiManager\Models\InstagramProfile;
use ScriptDevelop\InstagramApiManager\Models\InstagramReferral;
use ScriptDevelop\InstagramApiManager\Support\InstagramModelResolver;

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

    /**
     * Procesar un mensaje individual del webhook
     * Este es el punto de entrada principal para almacenar mensajes en BD
     */
    public function processWebhookMessage(array $messaging): void
    {
        Log::channel('instagram')->info('🔄 INICIANDO PROCESAMIENTO DE MENSAJE DEL WEBHOOK');
        
        try {
            // Llamar al método existente que maneja toda la lógica
            $this->processMessage($messaging);
            
            Log::channel('instagram')->info('✅ MENSAJE DEL WEBHOOK PROCESADO EXITOSAMENTE');
        } catch (\Exception $e) {
            Log::channel('instagram')->error('❌ ERROR AL PROCESAR MENSAJE DEL WEBHOOK:', [
                'error' => $e->getMessage(),
                'messaging' => $messaging
            ]);
            throw $e;
        }
    }

    public function processWebhookPayload(array $payload): void
    {
        try {
            Log::channel('instagram')->debug('Webhook payload received', ['payload' => $payload]);
            
            foreach ($payload['entry'] ?? [] as $entry) {
                foreach ($entry['messaging'] ?? [] as $messaging) {
                    Log::channel('instagram')->debug('Processing messaging entry', ['messaging' => $messaging]);
                    $this->processMessage($messaging);
                }
            }
        } catch (Exception $e) {
            Log::channel('instagram')->error('Error processing Instagram webhook:', ['error' => $e->getMessage()]);
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
    protected function processPostback(Model $conversation, array $postback, string $senderId, string $recipientId, $timestamp = null): void
    {
        $messageId = $postback['mid'] ?? 'postback_' . uniqid();

        $existingMessage = InstagramModelResolver::instagram_message()->where('message_id', $messageId)->first();
        if ($existingMessage) {
            Log::info('Postback duplicado ignorado', ['message_id' => $messageId]);
            return;
        }

        $messageData = [
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
            'sent_at' => $timestamp ? date('Y-m-d H:i:s', $timestamp / 1000) : now()
        ];

        InstagramModelResolver::instagram_message()->create($messageData);

        Log::info('Instagram postback processed', [
            'conversation_id' => $conversation->id,
            'postback' => $postback
        ]);

        $this->handlePostbackPayload($postback['payload'] ?? null, $conversation, $senderId, $recipientId);
    }

    /**
     * Procesar reacciones a mensajes
     */
    protected function processReaction(Model $conversation, array $reaction, string $senderId, string $recipientId): void
    {
        $reactedMessage = InstagramModelResolver::instagram_message()->where('message_id', $reaction['mid'] ?? '')->first();

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
        Log::channel('instagram')->info('═══════════════════════════════════════════════════════');
        Log::channel('instagram')->info('🔄 INICIANDO PROCESAMIENTO DE MENSAJE');
        Log::channel('instagram')->debug('Datos completos del mensaje:', $messageData);

        // Filtrar mensajes de eco (mensajes que nosotros enviamos)
        if (isset($messageData['message']['is_echo']) && $messageData['message']['is_echo'] === true) {
            Log::channel('instagram')->info('⏭️ Ignorando mensaje de eco (enviado por nosotros mismos)');
            return;
        }

        // Algunos eventos (como message_edit y read) no tienen sender y recipient en el nivel superior
        $senderId = $messageData['sender']['id'] ?? null;
        $recipientId = $messageData['recipient']['id'] ?? null;

        // Si no hay sender y recipient, puede ser un evento que no requiere procesamiento de mensaje
        if (!$senderId && !$recipientId) {
            Log::channel('instagram')->warning('⚠️ Evento sin sender o recipient, ignorando');
            return;
        }

        // Si solo falta uno, loggear advertencia
        if (!$senderId || !$recipientId) {
            Log::channel('instagram')->error('❌ Datos inválidos: falta sender o recipient', $messageData);
            return;
        }

        // Asegurar que sean strings
        if (is_array($senderId)) {
            $senderId = $senderId['id'] ?? (string) $senderId;
        }
        $senderId = (string) $senderId;
        
        if (is_array($recipientId)) {
            $recipientId = $recipientId['id'] ?? (string) $recipientId;
        }
        $recipientId = (string) $recipientId;

        try {
            Log::channel('instagram')->info('🔎 BUSCANDO CUENTA DE NEGOCIO EN BD', [
                'recipient_id' => $recipientId,
                'sender_id' => $senderId
            ]);

            // BUSCAR LA CUENTA DE NEGOCIO CORRECTAMENTE
            // Primero intentar buscar por instagram_business_account_id
            $businessAccount = InstagramModelResolver::instagram_business_account()->where('instagram_business_account_id', $recipientId)->first();

            // Si no se encuentra, buscar por user_id a través del perfil
            if (!$businessAccount) {
                Log::channel('instagram')->info('Buscando por perfil...');
                $profile = InstagramModelResolver::instagram_profile()->where('user_id', $recipientId)->first();
                if ($profile) {
                    $businessAccount = InstagramModelResolver::instagram_business_account()->where('instagram_business_account_id', $profile->instagram_business_account_id)->first();
                }
            }

            if (!$businessAccount) {
                Log::channel('instagram')->error('❌ LA CUENTA DE INSTAGRAM BUSINESS NO EXISTE EN BD', [
                    'recipient_id' => $recipientId,
                    'sender_id' => $senderId,
                    'hint' => 'Necesitas conectar la cuenta de Instagram primero'
                ]);
                return;
            }

            Log::channel('instagram')->info('✅ Cuenta de negocio encontrada', [
                'account_id' => $businessAccount->id,
                'instagram_business_account_id' => $businessAccount->instagram_business_account_id
            ]);
            
            // BUSCAR O CREAR LA CONVERSACIÓN
            Log::channel('instagram')->info('🔄 Buscando o creando conversación...');
            $conversation = $this->findOrCreateConversation($businessAccount->instagram_business_account_id, $senderId);
            Log::channel('instagram')->info('✅ Conversación lista', [
                'conversation_id' => $conversation->id,
                'participant_id' => $senderId
            ]);

            // ACTUALIZAR CONVERSACIÓN
            Log::channel('instagram')->info('⏰ Actualizando datos de conversación...');
            $conversation->update([
                'last_message_at' => now(),
                'updated_time' => now(),
                'unread_count' => $conversation->unread_count + 1
            ]);
            Log::channel('instagram')->info('✅ Conversación actualizada');

            // PROCESAR DIFERENTES TIPOS DE EVENTOS
            Log::channel('instagram')->info('📋 Determinando tipo de evento...');
            if (isset($messageData['message'])) {
                Log::channel('instagram')->info('→ Es un MENSAJE TEXT/MEDIA');
                $this->processIncomingMessage($conversation, $messageData['message'], $senderId, $businessAccount->instagram_business_account_id);

                // Verificar si es una default action de plantilla genérica
                if (isset($messageData['message']['quick_reply'])) {
                    // Es un quick reply, ya se procesa en processIncomingMessage
                } 
                
                // SOLO actualizar contacto para mensajes entrantes (no ecos)
                if (!isset($messageData['message']['is_echo']) || $messageData['message']['is_echo'] !== true) {
                    $this->updateContact($senderId, $businessAccount->instagram_business_account_id, $messageData);
                }
            } elseif (isset($messageData['postback'])) {
                Log::channel('instagram')->info('→ Es un POSTBACK (botón/acción)');
                $this->processPostback(
                    $conversation, 
                    $messageData['postback'], 
                    $senderId, 
                    $businessAccount->instagram_business_account_id,
                    $messageData['timestamp'] ?? null // Pasar el timestamp
                );
                $this->updateContact($senderId, $businessAccount->instagram_business_account_id, $messageData);
            } elseif (isset($messageData['reaction'])) {
                Log::channel('instagram')->info('→ Es una REACCIÓN (emoji)');
                $this->processReaction($conversation, $messageData['reaction'], $senderId, $businessAccount->instagram_business_account_id);
            } elseif (isset($messageData['optin'])) {
                Log::channel('instagram')->info('→ Es un OPT-IN');
                $this->processOptin($conversation, $messageData['optin'], $senderId, $businessAccount->instagram_business_account_id);
                $this->updateContact($senderId, $businessAccount->instagram_business_account_id, $messageData);
            } elseif (isset($messageData['referral'])) {
                Log::channel('instagram')->info('→ Es una REFERENCIA');
                $this->processReferral($conversation, $messageData['referral'], $senderId, $businessAccount->instagram_business_account_id);
                $this->updateContact($senderId, $businessAccount->instagram_business_account_id, $messageData);
            } elseif (isset($messageData['read'])) {
                Log::channel('instagram')->info('→ Es un EVENTO DE LECTURA');
                $this->processRead($conversation, $messageData['read'], $senderId, $businessAccount->instagram_business_account_id);
                // NO llamar a updateContact para eventos de lectura
            } elseif (isset($messageData['message_edit'])) {
                Log::channel('instagram')->info('→ Es una EDICIÓN DE MENSAJE');
                $this->processMessageEdit($conversation, $messageData['message_edit'], $senderId, $businessAccount->instagram_business_account_id);
                // NO llamar a updateContact para eventos de edición
            } elseif (isset($messageData['referral'])) {
                Log::channel('instagram')->info('→ Es una REFERENCIA (2)');
                $this->processReferral($conversation, $messageData['referral'], $senderId, $businessAccount->instagram_business_account_id);
                $this->updateContact($senderId, $businessAccount->instagram_business_account_id, $messageData);
            }else {
                Log::channel('instagram')->warning('⚠️ TIPO DE EVENTO DESCONOCIDO', $messageData);
            }

            Log::channel('instagram')->info('═══════════════════════════════════════════════════════');
            Log::channel('instagram')->info('✅ PROCESAMIENTO COMPLETADO EXITOSAMENTE');
            
        } catch (Exception $e) {
            Log::channel('instagram')->error('═══════════════════════════════════════════════════════');
            Log::channel('instagram')->error('❌ ERROR PROCESANDO MENSAJE INSTAGRAM:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Procesar mensajes entrantes de cualquier tipo
     * AQUÍ ES DONDE SE ALMACENA EL MENSAJE EN LA BASE DE DATOS
     */
    protected function processIncomingMessage(Model $conversation, array $message, string $senderId, string $recipientId): void
    {
        $messageId = $message['mid'] ?? uniqid();

        // Verificar si el mensaje ya existe para evitar duplicados
        $existingMessage = InstagramModelResolver::instagram_message()->where('message_id', $messageId)->first();
        if ($existingMessage) {
            Log::channel('instagram')->info('⚠️ Mensaje duplicado ignorado', ['message_id' => $messageId]);
            return;
        }

        $messageType = $this->determineMessageType($message);
        Log::channel('instagram')->info('📝 PREPARANDO DATOS PARA GUARDAR EN BD', [
            'conversation_id' => $conversation->id,
            'message_id' => $messageId,
            'type' => $messageType,
            'from' => $senderId,
            'to' => $recipientId
        ]);

        $messageData = [
            'conversation_id' => $conversation->id,
            'message_id' => $message['mid'] ?? uniqid(),
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
            'sent_at' => isset($message['timestamp']) ? 
                date('Y-m-d H:i:s', $message['timestamp'] / 1000) : 
                now()
        ];

        // Detectar y procesar quick replies
        if (isset($message['quick_reply'])) {
            $messageData['message_type'] = 'quick_reply';
            $messageData['message_context'] = 'quick_reply_response';
            $messageData['message_context_id'] = $message['quick_reply']['payload'] ?? null;
            $messageData['quick_reply_payload'] = $message['quick_reply']['payload'] ?? null;
            $messageData['context_message_text'] = $message['text'] ?? null;
            Log::channel('instagram')->info('💬 Quick reply recibido', [
                'payload' => $message['quick_reply']['payload'] ?? null,
                'text' => $message['text'] ?? null
            ]);
        }

        if (isset($message['postback'])) {
            $messageData['message_type'] = 'postback';
            $messageData['postback_payload'] = $message['postback']['payload'] ?? null;
            $messageData['context_message_text'] = $message['postback']['title'] ?? null;
        }

        // ⭐⭐⭐ AQUÍ SE ALMACENA EL MENSAJE EN LA BASE DE DATOS ⭐⭐⭐
        Log::channel('instagram')->info('💾 GUARDANDO MENSAJE EN LA BASE DE DATOS (tabla: instagram_messages)');
        $savedMessage = InstagramModelResolver::instagram_message()->create($messageData);
        Log::channel('instagram')->info('✅ MENSAJE GUARDADO EN BD', [
            'id' => $savedMessage->id,
            'message_id' => $savedMessage->message_id,
            'type' => $savedMessage->message_type,
            'from' => $savedMessage->message_from
        ]);

        // Procesar adjuntos si existen
        if (isset($message['attachments']) && is_array($message['attachments'])) {
            Log::channel('instagram')->info('📎 PROCESANDO ADJUNTOS', [
                'cantidad' => count($message['attachments'])
            ]);
            
            foreach ($message['attachments'] as $attachment) {
                if (isset($attachment['type']) && isset($attachment['payload']['url'])) {
                    if ($attachment['type'] === 'image' || $attachment['type'] === 'video' || $attachment['type'] === 'audio') {
                        $messageData['media_url'] = $attachment['payload']['url'];
                        Log::channel('instagram')->info('📎 Adjunto procesado', [
                            'type' => $attachment['type'],
                            'url' => $attachment['payload']['url']
                        ]);
                    }
                }
            }
        }

        Log::channel('instagram')->info('✨ RESUMEN FINAL DEL MENSAJE ALMACENADO', [
            'id_en_bd' => $savedMessage->id,
            'message_id' => $messageId,
            'tipo' => $messageType,
            'de' => $senderId,
            'para' => $recipientId,
            'contenido' => substr($message['text'] ?? 'N/A', 0, 50),
            'estado' => 'received',
            'tabla' => 'instagram_messages'
        ]);
    }
    
    // Añadir método para procesar ediciones de mensajes
    protected function processMessageEdit(Model $conversation, array $messageEdit, string $senderId, string $recipientId): void
    {
        Log::info('Edición de mensaje procesada', [
            'conversation_id' => $conversation->id,
            'message_edit' => $messageEdit
        ]);
        
        // Opcional: puedes implementar lógica para actualizar mensajes editados
    }
    protected function processOptin(Model $conversation, array $optin, string $senderId, string $recipientId): void
    {
        Log::info('Instagram optin processed', [
            'conversation_id' => $conversation->id,
            'optin' => $optin
        ]);
    }

    protected function processReferral(Model $conversation, array $referral, string $senderId, string $recipientId): void
    {
        Log::info('Instagram referral processed', [
            'conversation_id' => $conversation->id,
            'referral' => $referral
        ]);

        // Verificar si es un referral de ig.me
        if (isset($referral['source']) && $referral['source'] === 'SHORTLINKS') {
            $this->processIgMeReferral($conversation, $referral, $senderId, $recipientId);
        }
    }

    /**
     * Procesar referrals específicos de ig.me
     */
    protected function processIgMeReferral(Model $conversation, array $referral, string $senderId, string $recipientId): void
    {
        try {
            $ref = $referral['ref'] ?? null;
            $source = $referral['source'] ?? null;
            $type = $referral['type'] ?? null;
            
            // Guardar información del referral en la conversación
            $conversation->update([
                'last_referral' => $ref,
                'referral_source' => $source,
                'referral_type' => $type,
                'referral_timestamp' => now()
            ]);
            
            // Crear un registro detallado del referral
            InstagramModelResolver::instagram_referral()->create([
                'conversation_id' => $conversation->id,
                'instagram_user_id' => $senderId,
                'instagram_business_account_id' => $recipientId,
                'ref_parameter' => $ref,
                'source' => $source,
                'type' => $type,
                'processed_at' => now()
            ]);
            
            Log::info('ig.me referral processed', [
                'conversation_id' => $conversation->id,
                'ref' => $ref,
                'source' => $source,
                'type' => $type
            ]);
            
        } catch (Exception $e) {
            Log::error('Error processing ig.me referral:', [
                'error' => $e->getMessage(),
                'referral' => $referral
            ]);
        }
    }

    protected function processRead(Model $conversation, array $read, string $senderId, string $recipientId): void
    {
        if (isset($read['watermark'])) {
            InstagramModelResolver::instagram_message()->where('conversation_id', $conversation->id)
                ->where('created_time', '<=', date('Y-m-d H:i:s', $read['watermark'] / 1000))
                ->where('status', 'sent')
                ->update(['status' => 'read', 'read_at' => now()]);
        }
        
        // También puedes procesar el mid específico si está disponible
        if (isset($read['mid'])) {
            InstagramModelResolver::instagram_message()->where('message_id', $read['mid'])
                ->update(['status' => 'read', 'read_at' => now()]);
        }
        
        Log::info('Instagram read receipt processed', [
            'conversation_id' => $conversation->id,
            'read' => $read
        ]);
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
        
        return 'text';
    }

    protected function updateContact(string $instagramUserId, string $businessAccountId, array $messageData): void
    {
        try {
            $profile = $messageData['sender']['profile'] ?? [];
            
            Log::debug('Intentando actualizar contacto', [
                'user_id' => $instagramUserId,
                'business_account_id' => $businessAccountId,
                'has_profile_data' => !empty($profile)
            ]);
            
            // Si no hay información de perfil en el webhook, intentar obtenerla via API
            if (empty($profile)) {
                Log::info('No hay información de perfil en el webhook, intentando obtener via API');
                $profile = $this->getUserProfileViaApi($instagramUserId, $businessAccountId);
            }
            
            // Crear o actualizar el contacto incluso si no tenemos información completa
            InstagramModelResolver::instagram_contact()->updateOrCreate(
                [
                    'instagram_business_account_id' => $businessAccountId,
                    'instagram_user_id' => $instagramUserId
                ],
                [
                    'username' => $profile['username'] ?? null,
                    'profile_picture' => $profile['profile_pic'] ?? null,
                    'name' => $profile['name'] ?? null,
                    'last_interaction' => now(),
                ]
            );
            
            Log::info('Contacto actualizado/creado exitosamente', [
                'user_id' => $instagramUserId,
                'business_account_id' => $businessAccountId
            ]);
        } catch (Exception $e) {
            Log::error('Error updating Instagram contact:', [
                'error' => $e->getMessage(),
                'user_id' => $instagramUserId,
                'business_account_id' => $businessAccountId
            ]);
        }
    }

    public function markAsRead(string $messageId): bool
    {
        try {
            $message = InstagramModelResolver::instagram_message()->where('message_id', $messageId)->first();
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
    protected function sendMessageGeneric(string $recipientId, array $payload, string $messageType, ?string $conversationId = null, ?string $mediaUrl = null, ?string $postId = null): ?array
    {
        $this->validateCredentials();

        $conversation = $conversationId ? 
            InstagramModelResolver::instagram_conversation()->find($conversationId) :
            $this->findOrCreateConversation($this->instagramUserId, $recipientId);

        $messageData = [
            'conversation_id' => $conversation->id,
            'message_id' => 'temp_' . uniqid(),
            'message_method' => 'outgoing',
            'message_type' => $messageType,
            'message_from' => $this->instagramUserId,
            'message_to' => $recipientId,
            'json_content' => $payload,
            'json' => $payload,
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
        } elseif ($messageType === 'quick_reply') {
            $messageData['message_content'] = $payload['message']['text'];
            $messageData['json_content'] = ['quick_replies' => $payload['message']['quick_replies']];
        } elseif ($messageType === 'generic_template') {
            $messageData['message_content'] = 'Generic Template';
            $messageData['json_content'] = ['elements' => $payload['message']['attachment']['payload']['elements']];
        } elseif ($messageType === 'button_template') {
            $messageData['message_content'] = $payload['message']['attachment']['payload']['text'] ?? null;
            $messageData['json_content'] = [
                'buttons' => $payload['message']['attachment']['payload']['buttons'] ?? []
            ];
        }

        $message = InstagramModelResolver::instagram_message()->create($messageData);

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
    public function sendTextMessage(string $recipientId, string $text, ?string $conversationId = null): ?array
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
    public function sendImageMessage(string $recipientId, string $imageUrl, ?string $conversationId = null): ?array
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
    public function sendAudioMessage(string $recipientId, string $audioUrl, ?string $conversationId = null): ?array
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
    public function sendVideoMessage(string $recipientId, string $videoUrl, ?string $conversationId = null): ?array
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
    public function sendStickerMessage(string $recipientId, ?string $conversationId = null): ?array
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
    public function reactToMessage(string $recipientId, string $messageId, string $reaction = 'love', ?string $conversationId = null): ?array
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
    public function unreactToMessage(string $recipientId, string $messageId, ?string $conversationId = null): ?array
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
    public function sendPublishedPost(string $recipientId, string $postId, ?string $conversationId = null): ?array
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
    public function sendMessageWithTag(string $recipientId, string $text, string $tag, ?string $conversationId = null): ?array
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
    public function findOrCreateConversation(string $instagramAccountId, string $userId): Model
    {
        return InstagramModelResolver::instagram_conversation()->firstOrCreate(
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
                $conversation = InstagramModelResolver::instagram_conversation()->updateOrCreate(
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
    protected function syncConversationMessages(Model $conversation, string $accessToken): void
    {
        try {
            $messages = $this->getMessages($conversation->conversation_id);
            
            foreach ($messages['data'] ?? [] as $messageData) {
                InstagramModelResolver::instagram_message()->updateOrCreate(
                    ['message_id' => $messageData['id']],
                    [
                        'conversation_id' => $conversation->id,
                        'message_method' => $this->determineMessageMethod($messageData, $conversation->instagram_business_account_id),
                        'message_type' => $this->determineMessageTypeFromApi($messageData),
                        'message_from' => $messageData['from']['id'] ?? null,
                        'message_to' => $messageData['to']['data'][0]['id'] ?? null,
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
        $senderId = is_array($messageData['from']) ? 
            ($messageData['from']['id'] ?? null) : 
            $messageData['from'];
        
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

    /**
     * Obtener información del perfil de un usuario de Instagram via API
     * según la documentación oficial de Meta
     */
    protected function getUserProfileViaApi(string $userId, string $businessAccountId): array
    {
        try {
            $businessAccount = InstagramModelResolver::instagram_business_account()->where('instagram_business_account_id', $businessAccountId)->first();

            if (!$businessAccount || !$businessAccount->access_token) {
                Log::warning('No se puede obtener perfil via API: falta access token o cuenta no encontrada');
                return [];
            }
            
            // Verificar que la cuenta tenga el permiso necesario
            if (!app(InstagramAccountService::class)->hasPermission($businessAccount, 'instagram_business_basic')) {
                Log::warning('No se puede obtener perfil via API: falta permiso instagram_business_basic');
                return [];
            }
            
            // Usar el endpoint correcto según la documentación de Instagram
            // https://graph.instagram.com/{user-id}?fields={fields}&access_token={access-token}
            $response = $this->apiClient->request(
                'GET',
                $userId,
                [],
                null,
                [
                    'access_token' => $businessAccount->access_token,
                    'fields' => 'username,name,profile_pic,follower_count,is_user_follow_business,is_business_follow_user,is_verified_user'
                ]
            );
            
            Log::debug('Respuesta de API para perfil de usuario:', ['response' => $response]);
            
            return [
                'username' => $response['username'] ?? null,
                'name' => $response['name'] ?? null,
                'profile_pic' => $response['profile_pic'] ?? null,
                'follower_count' => $response['follower_count'] ?? null,
                'is_verified' => $response['is_verified_user'] ?? false
            ];
        } catch (Exception $e) {
            Log::error('Error obteniendo perfil via API:', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'business_account_id' => $businessAccountId
            ]);
            return [];
        }
    }

    /**
     * Enviar un mensaje con Quick Replies
     */
    public function sendQuickReplies(string $recipientId, string $text, array $quickReplies, ?string $conversationId = null): ?array
    {
        // Validar quick replies
        $this->validateQuickReplies($quickReplies);
        
        $payload = [
            'recipient' => [
                'id' => $recipientId
            ],
            'messaging_type' => 'RESPONSE',
            'message' => [
                'text' => $text,
                'quick_replies' => $quickReplies
            ]
        ];

        return $this->sendMessageGeneric($recipientId, $payload, 'quick_reply', $conversationId);
    }

    /**
     * Validar quick replies antes de enviarlos
     */
    protected function validateQuickReplies(array $quickReplies): bool
    {
        if (count($quickReplies) > 13) {
            throw new Exception('Máximo 13 quick replies permitidos');
        }
        
        foreach ($quickReplies as $quickReply) {
            if (!isset($quickReply['content_type']) || $quickReply['content_type'] !== 'text') {
                throw new Exception('Quick replies solo soportan content_type: text');
            }
            
            if (!isset($quickReply['title']) || empty($quickReply['title'])) {
                throw new Exception('Cada quick reply debe tener un título');
            }
            
            if (strlen($quickReply['title']) > 20) {
                throw new Exception('El título del quick reply no puede exceder 20 caracteres');
            }
            
            if (!isset($quickReply['payload']) || empty($quickReply['payload'])) {
                throw new Exception('Cada quick reply debe tener un payload');
            }
        }
        
        return true;
    }

    /**
     * Enviar una plantilla genérica
     */
    public function sendGenericTemplate(string $recipientId, array $elements, ?string $conversationId = null): ?array
    {
        // Validar elementos de la plantilla
        $this->validateGenericTemplateElements($elements);
        
        $payload = [
            'recipient' => [
                'id' => $recipientId
            ],
            'message' => [
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'generic',
                        'elements' => $elements
                    ]
                ]
            ]
        ];

        return $this->sendMessageGeneric($recipientId, $payload, 'generic_template', $conversationId);
    }

    /**
     * Validar elementos de plantilla genérica
     */
    protected function validateGenericTemplateElements(array $elements): bool
    {
        if (count($elements) > 10) {
            throw new Exception('Máximo 10 elementos permitidos en la plantilla genérica');
        }
        
        foreach ($elements as $element) {
            if (!isset($element['title']) || empty($element['title'])) {
                throw new Exception('Cada elemento debe tener un título');
            }
            
            if (strlen($element['title']) > 80) {
                throw new Exception('El título no puede exceder 80 caracteres');
            }
            
            if (isset($element['subtitle']) && strlen($element['subtitle']) > 80) {
                throw new Exception('El subtítulo no puede exceder 80 caracteres');
            }
            
            // Validar botones si existen
            if (isset($element['buttons'])) {
                if (count($element['buttons']) > 3) {
                    throw new Exception('Máximo 3 botones por elemento');
                }
                
                foreach ($element['buttons'] as $button) {
                    if (!isset($button['type']) || !in_array($button['type'], ['web_url', 'postback'])) {
                        throw new Exception('Tipo de botón no válido. Solo se permiten web_url y postback');
                    }
                    
                    if (!isset($button['title']) || empty($button['title'])) {
                        throw new Exception('Cada botón debe tener un título');
                    }
                    
                    if ($button['type'] == 'web_url' && !isset($button['url'])) {
                        throw new Exception('Los botones web_url requieren una URL');
                    }
                    
                    if ($button['type'] == 'postback' && !isset($button['payload'])) {
                        throw new Exception('Los botones postback requieren un payload');
                    }
                }
            }
        }
        
        return true;
    }

    /**
     * Manejar la lógica genérica de postbacks sin respuestas hardcodeadas
     */
    protected function handlePostbackPayload(?string $payload, Model $conversation, string $senderId, string $recipientId): void
    {
        if (!$payload) {
            return;
        }

        try {
            $businessAccount = $conversation->instagramBusinessAccount;
            
            if (!$businessAccount) {
                Log::error('No se pudo encontrar la cuenta de negocio para manejar el postback');
                return;
            }

            // Registrar el postback en la base de datos para su posterior procesamiento
            $this->logPostbackInteraction($payload, $conversation, $senderId, $recipientId);
            
            // Aquí podrías integrar con un sistema de automatización externo
            // Por ahora, solo registramos la interacción
            Log::info('Postback recibido y registrado', [
                'payload' => $payload,
                'conversation_id' => $conversation->id,
                'sender_id' => $senderId
            ]);

        } catch (Exception $e) {
            Log::error('Error manejando postback payload:', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
        }
    }

    /**
     * Registrar la interacción de postback en la base de datos
     */
    protected function logPostbackInteraction(string $payload, Model $conversation, string $senderId, string $recipientId): void
    {
        try {
            // Crear un registro de interacción (puedes crear una tabla específica para esto)
            // Por ahora, usaremos la tabla de mensajes existente
            $messageId = 'postback_interaction_' . uniqid();

            InstagramModelResolver::instagram_message()->create([
                'conversation_id' => $conversation->id,
                'message_id' => $messageId,
                'message_method' => 'incoming',
                'message_type' => 'postback_interaction',
                'message_from' => $senderId,
                'message_to' => $recipientId,
                'message_content' => 'Postback interaction',
                'message_context' => 'user_interaction',
                'message_context_id' => $payload,
                'json_content' => [
                    'payload' => $payload,
                    'conversation_id' => $conversation->id,
                    'timestamp' => now()
                ],
                'status' => 'processed',
                'created_time' => now()
            ]);
            
            // También podrías actualizar la conversación para indicar la última interacción
            $conversation->update([
                'last_interaction_at' => now(),
                'last_interaction_type' => 'postback',
                'last_interaction_payload' => $payload,
                'updated_time' => now()
            ]);
            
        } catch (Exception $e) {
            Log::error('Error registrando interacción de postback:', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
        }
    }

    /**
     * Enviar una plantilla de botones
     */
    public function sendButtonTemplate(string $recipientId, string $text, array $buttons, ?string $conversationId = null): ?array
    {
        // Validar botones
        $this->validateButtonTemplate($text, $buttons);
        
        $payload = [
            'recipient' => [
                'id' => $recipientId
            ],
            'message' => [
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'button',
                        'text' => $text,
                        'buttons' => $buttons
                    ]
                ]
            ]
        ];

        return $this->sendMessageGeneric($recipientId, $payload, 'button_template', $conversationId);
    }

    /**
     * Validar plantilla de botones
     */
    protected function validateButtonTemplate(string $text, array $buttons): bool
    {
        // Validar texto
        if (empty($text)) {
            throw new Exception('El texto de la plantilla de botones no puede estar vacío');
        }
        
        if (strlen($text) > 640) {
            throw new Exception('El texto de la plantilla de botones no puede exceder 640 caracteres');
        }
        
        // Validar botones
        if (count($buttons) < 1 || count($buttons) > 3) {
            throw new Exception('Debe haber entre 1 y 3 botones');
        }
        
        foreach ($buttons as $button) {
            if (!isset($button['type']) || !in_array($button['type'], ['web_url', 'postback'])) {
                throw new Exception('Tipo de botón no válido. Solo se permiten web_url y postback');
            }
            
            if (!isset($button['title']) || empty($button['title'])) {
                throw new Exception('Cada botón debe tener un título');
            }
            
            if (strlen($button['title']) > 20) {
                throw new Exception('El título del botón no puede exceder 20 caracteres');
            }
            
            if ($button['type'] == 'web_url') {
                if (!isset($button['url']) || empty($button['url'])) {
                    throw new Exception('Los botones web_url requieren una URL');
                }
            } elseif ($button['type'] == 'postback') {
                if (!isset($button['payload']) || empty($button['payload'])) {
                    throw new Exception('Los botones postback requieren un payload');
                }
            }
        }
        
        return true;
    }
}