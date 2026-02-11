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

        // 1. DETECCIÓN DE MENSAJES DE ECO
        // En echo: sender = business (quien envió), recipient = usuario/contacto (quien recibió)
        $isEcho = isset($messageData['message']['is_echo']) && $messageData['message']['is_echo'] === true;
        if ($isEcho) {
            Log::channel('instagram')->info('📤 Mensaje de eco detectado (enviado por nosotros). Se actualizará el contacto del destinatario.');
        }

        // 2. EXTRACCIÓN DE SENDER Y RECIPIENT
        // Algunos eventos (message_edit, read, reaction) NO tienen sender/recipient en el nivel superior
        $senderId = $messageData['sender']['id'] ?? null;
        $recipientId = $messageData['recipient']['id'] ?? null;

        // 3. BÚSQUEDA DE CONTEXTO POR M.I.D. (MESSAGE ID) DE RESPALDO
        // Si faltan sender/recipient, intentamos buscar el mensaje original en la BD usando el mid
        if (!$senderId || !$recipientId) {
            $mid = null;

            // Buscar 'mid' en diferentes lugares posibles según el tipo de evento
            if (isset($messageData['message_edit']['mid'])) {
                $mid = $messageData['message_edit']['mid'];
            } elseif (isset($messageData['read']['mid'])) {
                $mid = $messageData['read']['mid'];
            } elseif (isset($messageData['reaction']['mid'])) {
                $mid = $messageData['reaction']['mid'];
            } elseif (isset($messageData['message']['mid'])) {
                $mid = $messageData['message']['mid'];
            }

            if ($mid) {
                Log::channel('instagram')->info('🔍 Buscando contexto por Message ID (mid)', ['mid' => $mid]);
                $originalMessage = InstagramModelResolver::instagram_message()->where('message_id', $mid)->first();

                if ($originalMessage) {
                    $senderId = $originalMessage->message_from;
                    $recipientId = $originalMessage->message_to;

                    Log::channel('instagram')->info('✅ Contexto recuperado de BD', [
                        'conversation_id' => $originalMessage->conversation_id,
                        'original_from' => $senderId,
                        'original_to' => $recipientId
                    ]);
                } else {
                    Log::channel('instagram')->warning('⚠️ Mensaje original no encontrado por mid', ['mid' => $mid]);
                }
            }
        }

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
            // Para echo: business = sender, contacto = recipient
            // Para mensaje normal: business = recipient, contacto = sender
            $businessIdToSearch = $isEcho ? $senderId : $recipientId;
            $contactUserId = $isEcho ? $recipientId : $senderId;

            Log::channel('instagram')->info('🔎 BUSCANDO CUENTA DE NEGOCIO EN BD', [
                'business_id_to_search' => $businessIdToSearch,
                'contact_user_id' => $contactUserId,
                'is_echo' => $isEcho
            ]);

            // BUSCAR LA CUENTA DE NEGOCIO
            // Los webhooks de Instagram envían entry.id, recipient.id y sender.id que corresponden
            // al user_id de instagram_profile. Buscar por ese campo en primer lugar.
            $profile = InstagramModelResolver::instagram_profile()
                ->where('user_id', $businessIdToSearch)
                ->first();

            if ($profile) {
                Log::channel('instagram')->info('✅ Perfil encontrado por user_id (instagram_profile)');
                $businessAccount = InstagramModelResolver::instagram_business_account()
                    ->where('instagram_business_account_id', $profile->instagram_business_account_id)
                    ->first();
            } else {
                // Fallback: buscar por instagram_scoped_id
                $profile = InstagramModelResolver::instagram_profile()
                    ->where('instagram_scoped_id', $businessIdToSearch)
                    ->first();

                if ($profile) {
                    Log::channel('instagram')->info('✅ Perfil encontrado por instagram_scoped_id');
                    $businessAccount = InstagramModelResolver::instagram_business_account()
                        ->where('instagram_business_account_id', $profile->instagram_business_account_id)
                        ->first();
                } else {
                    // Fallback: buscar por instagram_business_account_id directamente
                    Log::channel('instagram')->info('Buscando por instagram_business_account_id...');
                    $businessAccount = InstagramModelResolver::instagram_business_account()
                        ->where('instagram_business_account_id', $businessIdToSearch)
                        ->first();

                    if ($businessAccount) {
                        $profile = InstagramModelResolver::instagram_profile()
                            ->where('instagram_business_account_id', $businessAccount->instagram_business_account_id)
                            ->first();

                        if ($profile && !$profile->instagram_scoped_id) {
                            $profile->update(['instagram_scoped_id' => $businessIdToSearch]);
                            Log::channel('instagram')->info('✅ IGSID guardado para futuros webhooks');
                        }
                    }
                }
            }

            if (!$businessAccount) {
                Log::channel('instagram')->error('❌ LA CUENTA DE INSTAGRAM BUSINESS NO EXISTE EN BD', [
                    'business_id_to_search' => $businessIdToSearch,
                    'contact_user_id' => $contactUserId,
                    'hint' => 'Necesitas conectar la cuenta de Instagram primero'
                ]);
                return;
            }

            Log::channel('instagram')->info('✅ Cuenta de negocio encontrada', [
                'account_id' => $businessAccount->id,
                'instagram_business_account_id' => $businessAccount->instagram_business_account_id
            ]);

            // Establecer token de acceso para obtener perfiles vía API
            $this->withAccessToken($businessAccount->access_token)
                 ->withInstagramUserId($businessAccount->instagram_business_account_id);

            // BUSCAR O CREAR LA CONVERSACIÓN (siempre entre business y el usuario/contacto)
            Log::channel('instagram')->info('🔄 Buscando o creando conversación...');
            $conversation = $this->findOrCreateConversation($businessAccount->instagram_business_account_id, $contactUserId);
            Log::channel('instagram')->info('✅ Conversación lista', [
                'conversation_id' => $conversation->id,
                'participant_id' => $senderId
            ]);

            // ACTUALIZAR CONVERSACIÓN (no incrementar unread para ecos - es nuestro mensaje)
            Log::channel('instagram')->info('⏰ Actualizando datos de conversación...');
            $conversation->update([
                'last_message_at' => now(),
                'updated_time' => now(),
                'unread_count' => $isEcho ? $conversation->unread_count : $conversation->unread_count + 1
            ]);
            Log::channel('instagram')->info('✅ Conversación actualizada');

            // PROCESAR DIFERENTES TIPOS DE EVENTOS
            Log::channel('instagram')->info('📋 Determinando tipo de evento...');
            if (isset($messageData['message'])) {
                if ($isEcho) {
                    // Mensaje de eco: no guardar el mensaje (es nuestro), pero SÍ actualizar el contacto del destinatario
                    Log::channel('instagram')->info('→ Mensaje ECO: actualizando contacto del destinatario');
                    $this->updateContact($contactUserId, $businessAccount->instagram_business_account_id, $messageData);
                } else {
                    Log::channel('instagram')->info('→ Es un MENSAJE TEXT/MEDIA entrante');
                    $this->processIncomingMessage($conversation, $messageData['message'], $contactUserId, $businessAccount->instagram_business_account_id);
                    if (isset($messageData['message']['quick_reply'])) {
                        // Es un quick reply, ya se procesa en processIncomingMessage
                    }
                    $this->updateContact($contactUserId, $businessAccount->instagram_business_account_id, $messageData);
                }
            } elseif (isset($messageData['postback'])) {
                Log::channel('instagram')->info('→ Es un POSTBACK (botón/acción)');
                $this->processPostback(
                    $conversation,
                    $messageData['postback'],
                    $contactUserId,
                    $businessAccount->instagram_business_account_id,
                    $messageData['timestamp'] ?? null // Pasar el timestamp
                );
                $this->updateContact($contactUserId, $businessAccount->instagram_business_account_id, $messageData);
            } elseif (isset($messageData['reaction'])) {
                Log::channel('instagram')->info('→ Es una REACCIÓN (emoji)');
                $this->processReaction($conversation, $messageData['reaction'], $contactUserId, $businessAccount->instagram_business_account_id);
            } elseif (isset($messageData['optin'])) {
                Log::channel('instagram')->info('→ Es un OPT-IN');
                $this->processOptin($conversation, $messageData['optin'], $contactUserId, $businessAccount->instagram_business_account_id);
                $this->updateContact($contactUserId, $businessAccount->instagram_business_account_id, $messageData);
            } elseif (isset($messageData['referral'])) {
                Log::channel('instagram')->info('→ Es una REFERENCIA');
                $this->processReferral($conversation, $messageData['referral'], $contactUserId, $businessAccount->instagram_business_account_id);
                $this->updateContact($contactUserId, $businessAccount->instagram_business_account_id, $messageData);
            } elseif (isset($messageData['read'])) {
                Log::channel('instagram')->info('→ Es un EVENTO DE LECTURA');
                $this->processRead($conversation, $messageData['read'], $contactUserId, $businessAccount->instagram_business_account_id);
                // NO llamar a updateContact para eventos de lectura
            } elseif (isset($messageData['message_edit'])) {
                Log::channel('instagram')->info('→ Es una EDICIÓN DE MENSAJE');
                $this->processMessageEdit($conversation, $messageData['message_edit'], $contactUserId, $businessAccount->instagram_business_account_id);
                // NO llamar a updateContact para eventos de edición
            } elseif (isset($messageData['referral'])) {
                Log::channel('instagram')->info('→ Es una REFERENCIA (2)');
                $this->processReferral($conversation, $messageData['referral'], $contactUserId, $businessAccount->instagram_business_account_id);
                $this->updateContact($contactUserId, $businessAccount->instagram_business_account_id, $messageData);
            } else {
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
        $mid = $messageEdit['mid'] ?? null;

        if (!$mid) {
            Log::warning('Edición de mensaje sin ID (mid)', $messageEdit);
            return;
        }

        Log::channel('instagram')->info('📝 Procesando edición de mensaje', [
            'conversation_id' => $conversation->id,
            'mid' => $mid
        ]);

        // Buscar el mensaje en BD
        $message = InstagramModelResolver::instagram_message()->where('message_id', $mid)->first();

        if ($message) {
            // Marcar como editado
            $message->update([
                'is_edited' => true,
                'edited_at' => now(),
            ]);

            Log::channel('instagram')->info('✅ Mensaje marcado como editado en BD');
        } else {
            Log::channel('instagram')->warning('⚠️ Mensaje original no encontrado al procesar edición');
        }
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

            Log::channel('instagram')->debug('Actualizando contacto', [
                'user_id' => $instagramUserId,
                'business_account_id' => $businessAccountId,
                'has_profile_in_webhook' => !empty($profile)
            ]);

            // El webhook no incluye perfil; obtenerlo vía API: GET graph.instagram.com/{ig_scoped_id}
            if (empty($profile)) {
                Log::channel('instagram')->info('Obteniendo perfil del contacto vía API de Instagram');
                $profile = $this->getUserProfileViaApi($instagramUserId, $businessAccountId);
            }

            // Crear o actualizar el contacto con datos de perfil
            InstagramModelResolver::instagram_contact()->updateOrCreate(
                [
                    'instagram_business_account_id' => $businessAccountId,
                    'instagram_user_id' => $instagramUserId
                ],
                [
                    'username' => $profile['username'] ?? null,
                    'name' => $profile['name'] ?? null,
                    'profile_picture' => $profile['profile_pic'] ?? null,
                    'last_interaction_at' => now(),
                    'is_verified_user' => $profile['is_verified_user'] ?? null,
                    'follower_count' => $profile['follower_count'] ?? null,
                    'is_user_follow_business' => $profile['is_user_follow_business'] ?? null,
                    'is_business_follow_user' => $profile['is_business_follow_user'] ?? null,
                    'profile_synced_at' => !empty($profile) ? now() : null,
                ]
            );

            Log::channel('instagram')->info('Contacto actualizado/creado exitosamente', [
                'user_id' => $instagramUserId,
                'business_account_id' => $businessAccountId
            ]);
        } catch (Exception $e) {
            Log::channel('instagram')->error('Error actualizando contacto de Instagram:', [
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
     * Enviar una plantilla genérica
     */
    public function sendGenericTemplate(string $recipientId, array $elements, ?string $conversationId = null): ?array
    {
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
     * Enviar una plantilla de botones
     */
    public function sendButtonTemplate(string $recipientId, string $text, array $buttons, ?string $conversationId = null): ?array
    {
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
     * Enviar un post compartido (media share)
     */
    public function sendSharedPost(string $recipientId, string $postId, ?string $conversationId = null): ?array
    {
        $payload = [
            'recipient' => [
                'id' => $recipientId
            ],
            'message' => [
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'media',
                        'elements' => [
                            [
                                'media_type' => 'image',
                                'url' => 'https://www.facebook.com/' . $postId
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $this->sendMessageGeneric($recipientId, $payload, 'post', $conversationId, null, $postId);
    }

    /**
     * Marcar mensaje como visto (enviar acción de lectura)
     */
    public function sendReadReceipt(string $recipientId): ?array
    {
        $this->validateCredentials();

        try {
            return $this->apiClient->request(
                'POST',
                $this->instagramUserId . '/messages',
                [],
                [
                    'recipient' => [
                        'id' => $recipientId
                    ],
                    'sender_action' => 'mark_seen'
                ],
                [
                    'access_token' => $this->accessToken
                ]
            );
        } catch (Exception $e) {
            Log::error('Error sending read receipt:', [
                'error' => $e->getMessage(),
                'recipient_id' => $recipientId
            ]);
            return null;
        }
    }

    /**
     * Validar que las credenciales estén configuradas
     */
    protected function validateCredentials(): void
    {
        if (!$this->accessToken || !$this->instagramUserId) {
            throw new Exception('Access Token and Instagram User ID must be set.');
        }
    }

    /**
     * Encontrar o crear una conversación
     */
    public function findOrCreateConversation(string $instagramBusinessAccountId, string $instagramUserId): Model
    {
        // Generar un ID único basado en los participantes (siempre ordenados)
        $conversationId = $this->generateConversationId($instagramBusinessAccountId, $instagramUserId);

        Log::info('Buscando conversación:', [
            'generated_id' => $conversationId,
            'business_id' => $instagramBusinessAccountId,
            'user_id' => $instagramUserId
        ]);

        return InstagramModelResolver::instagram_conversation()->firstOrCreate(
            ['id' => $conversationId],
            [
                'instagram_business_account_id' => $instagramBusinessAccountId,
                'instagram_user_id' => $instagramUserId,
                //'title' => 'Conversación Instagram', // Opcional
                'updated_time' => now(),
                'unread_count' => 0
            ]
        );
    }

    /**
     * Obtener el perfil de un usuario de Instagram via API.
     * Endpoint: GET https://graph.instagram.com/{api_version}/{ig_scoped_id}
     * Requiere que el usuario haya dado consentimiento (enviado mensaje, click en icebreaker, etc.)
     */
    public function getUserProfileViaApi(string $instagramUserId, string $businessAccountId): array
    {
        $this->validateCredentials();

        try {
            $response = $this->apiClient->request(
                'GET',
                $instagramUserId,
                [],
                null,
                [
                    'fields' => 'name,username,profile_pic,is_verified_user,follower_count,is_user_follow_business,is_business_follow_user',
                    'access_token' => $this->accessToken
                ]
            );

            return is_array($response) ? $response : [];
        } catch (Exception $e) {
            Log::warning('No se pudo obtener el perfil del usuario de Instagram:', [
                'user_id' => $instagramUserId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    // Método helper para verificar si este webhook ya fue procesado
    protected function isDuplicate(string $messageId): bool
    {
        return InstagramModelResolver::instagram_message()->where('message_id', $messageId)->exists();
    }
}