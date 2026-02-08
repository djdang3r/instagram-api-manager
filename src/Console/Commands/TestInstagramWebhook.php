<?php

namespace ScriptDevelop\InstagramApiManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestInstagramWebhook extends Command
{
    protected $signature = 'instagram:test-webhook {--type=message : El tipo de evento (message, postback, image, reaction)}';
    
    protected $description = 'Simula eventos del webhook de Instagram para testing';

    public function handle()
    {
        $type = $this->option('type');
        $this->info('🧪 Iniciando test del webhook de Instagram');
        
        $baseUrl = config('app.url');
        $webhookUrl = route('instagram.webhook', [], false);
        $fullUrl = rtrim($baseUrl, '/') . '/' . ltrim($webhookUrl, '/');

        $this->info("URL del webhook: $fullUrl");

        // Generar payload según tipo
        $payload = match($type) {
            'message' => $this->generateMessagePayload(),
            'postback' => $this->generatePostbackPayload(),
            'image' => $this->generateImagePayload(),
            'reaction' => $this->generateReactionPayload(),
            default => $this->generateMessagePayload()
        };

        $this->info("\n📨 Enviando payload tipo: {$type}");
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Enviar POST al webhook
        try {
            $this->info("\n⏳ Enviando POST al webhook...");
            
            $response = Http::post($fullUrl, $payload);

            $this->info("✅ Respuesta recibida");
            $this->line("Status: " . $response->status());
            $this->line("Body: " . $response->body());

            if ($response->successful()) {
                $this->info("\n✅ Webhook procesó correctamente el evento");
                $this->info("\nRevisa los logs con:");
                $this->line("tail -f storage/logs/instagram.log");
            } else {
                $this->error("\n❌ El webhook retornó error");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error al enviar POST: " . $e->getMessage());
        }

        Log::channel('instagram')->info('🧪 Test de webhook ejecutado', [
            'type' => $type,
            'webhook_url' => $fullUrl
        ]);
    }

    protected function generateMessagePayload(): array
    {
        return [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => '12345678901234567',
                    'time' => time(),
                    'messaging' => [
                        [
                            'sender' => ['id' => '987654321'],
                            'recipient' => ['id' => '123456789'],
                            'timestamp' => time() * 1000,
                            'message' => [
                                'mid' => 'mid.' . uniqid(),
                                'text' => 'Hola, este es un mensaje de prueba desde la consola'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    protected function generatePostbackPayload(): array
    {
        return [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => '12345678901234567',
                    'time' => time(),
                    'messaging' => [
                        [
                            'sender' => ['id' => '987654321'],
                            'recipient' => ['id' => '123456789'],
                            'timestamp' => time() * 1000,
                            'postback' => [
                                'mid' => 'postback_' . uniqid(),
                                'title' => 'Botón de Prueba',
                                'payload' => 'TEST_BUTTON_PAYLOAD'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    protected function generateImagePayload(): array
    {
        return [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => '12345678901234567',
                    'time' => time(),
                    'messaging' => [
                        [
                            'sender' => ['id' => '987654321'],
                            'recipient' => ['id' => '123456789'],
                            'timestamp' => time() * 1000,
                            'message' => [
                                'mid' => 'mid.' . uniqid(),
                                'text' => 'Mira esta imagen',
                                'attachments' => [
                                    [
                                        'type' => 'image',
                                        'payload' => [
                                            'url' => 'https://via.placeholder.com/300x300?text=Test+Image'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    protected function generateReactionPayload(): array
    {
        return [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => '12345678901234567',
                    'time' => time(),
                    'messaging' => [
                        [
                            'sender' => ['id' => '987654321'],
                            'recipient' => ['id' => '123456789'],
                            'timestamp' => time() * 1000,
                            'reaction' => [
                                'mid' => 'mid.original',
                                'reaction' => 'smile',
                                'emoji' => '😊'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
