<?php

namespace ScriptDevelop\InstagramApiManager\Console\Commands;

use Illuminate\Console\Command;
use ScriptDevelop\InstagramApiManager\Models\InstagramMessage;
use ScriptDevelop\InstagramApiManager\Services\InstagramMessageService;

class ProcessPendingMessages extends Command
{
    protected $signature = 'instagram:messages:process';
    protected $description = 'Process pending Instagram messages';

    public function handle()
    {
        $pendingMessages = InstagramMessage::where('status', 'pending')
            ->where('message_method', 'outgoing')
            ->with('conversation.instagramBusinessAccount')
            ->get();

        foreach ($pendingMessages as $message) {
            try {
                $account = $message->conversation->instagramBusinessAccount;
                
                $service = app(InstagramMessageService::class)
                    ->withAccessToken($account->access_token)
                    ->withInstagramUserId($account->instagram_business_account_id);

                // Determinar el tipo de mensaje y enviarlo
                switch ($message->message_type) {
                    case 'text':
                        $result = $service->sendTextMessage(
                            $message->recipient,
                            $message->message_content,
                            $message->conversation_id
                        );
                        break;
                    case 'image':
                        $result = $service->sendImageMessage(
                            $message->recipient,
                            $message->media_url,
                            $message->conversation_id
                        );
                        break;
                    case 'audio':
                        $result = $service->sendAudioMessage(
                            $message->recipient,
                            $message->media_url,
                            $message->conversation_id
                        );
                        break;
                    case 'video':
                        $result = $service->sendVideoMessage(
                            $message->recipient,
                            $message->media_url,
                            $message->conversation_id
                        );
                        break;
                    case 'sticker':
                        $result = $service->sendStickerMessage(
                            $message->recipient,
                            $message->conversation_id
                        );
                        break;
                    case 'post':
                        $result = $service->sendPublishedPost(
                            $message->recipient,
                            $message->message_context_id, // ID del post
                            $message->conversation_id
                        );
                        break;
                    case 'reaction':
                        // Para reacciones, necesitamos el message_id de la reacción
                        $reactionData = $message->json_content ?? [];
                        $messageId = $reactionData['message_id'] ?? null;
                        $reaction = $reactionData['reaction'] ?? 'like';
                        if ($messageId) {
                            $result = $service->reactToMessage(
                                $message->recipient,
                                $messageId,
                                $reaction,
                                $message->conversation_id
                            );
                        } else {
                            $this->error("Falta message_id para la reacción del mensaje {$message->id}");
                            continue 2;
                        }
                        break;
                    default:
                        $this->error("Tipo de mensaje no soportado: {$message->message_type}");
                        continue 2;
                }

                if ($result) {
                    $message->update(['status' => 'sent', 'sent_at' => now()]);
                    $this->info("Mensaje {$message->id} enviado correctamente");
                } else {
                    $message->update(['status' => 'failed', 'failed_at' => now()]);
                    $this->error("Error enviando mensaje {$message->id}");
                }

            } catch (\Exception $e) {
                $message->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'message_error' => $e->getMessage()
                ]);
                $this->error("Error procesando mensaje {$message->id}: {$e->getMessage()}");
            }
        }
    }
}