<?php

namespace ScriptDevelop\InstagramApiManager\Console\Commands;

use Illuminate\Console\Command;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\warning;

class InstallInstagramApiManager extends Command
{
    protected $signature = 'instagram:install {--force : Sobrescribir archivos existentes}';

    protected $description = 'Instalación guiada de Instagram API Manager';

    public function handle(): int
    {
        intro('INSTAGRAM API MANAGER — Asistente de instalación');

        if (empty(config('app.key'))) {
            warning('No se encontró APP_KEY en tu configuración.');
            note('Ejecuta: php artisan key:generate');

            return self::FAILURE;
        }

        // ── 1. Publicar configuración ──────────────────────────────────────
        spin(function () {
            $this->callSilent('vendor:publish', [
                '--tag'   => 'instagram-config',
                '--force' => $this->option('force'),
            ]);
        }, 'Publicando configuración de Instagram...');

        spin(function () {
            $this->callSilent('vendor:publish', [
                '--tag'   => 'instagram-facebook-config',
                '--force' => $this->option('force'),
            ]);
        }, 'Publicando configuración de Facebook...');

        $this->components->info('Configuración publicada en config/instagram.php y config/facebook.php.');

        // ── 2. Migraciones ────────────────────────────────────────────────
        note('Las migraciones se cargan automáticamente por el paquete.');
        note('No necesitás publicarlas para que funcionen.');

        $publishMigrations = confirm(
            label: '¿Publicar migraciones en database/migrations/?',
            default: false,
            hint: 'Copiarlas a tu proyecto para revisarlas o modificarlas.'
        );

        if ($publishMigrations) {
            spin(function () {
                $this->callSilent('vendor:publish', [
                    '--tag'   => 'instagram-migrations',
                    '--force' => true,
                ]);
            }, 'Publicando migraciones...');

            $this->components->info('Migraciones publicadas en database/migrations/');
            note('Las migraciones publicadas siempre se sobrescriben con la última versión del paquete.');
        }

        $runMigrations = confirm(
            label: '¿Ejecutar las migraciones ahora?',
            default: true,
            hint: 'Aplica las tablas (oauth_states, instagram_business_accounts, etc.) a la base de datos.'
        );

        if ($runMigrations) {
            spin(function () {
                $this->call('migrate');
            }, 'Ejecutando migraciones...');

            $this->components->info('Migraciones ejecutadas.');
        }

        // ── 3. Storage Link ───────────────────────────────────────────────
        $runStorageLink = confirm(
            label: '¿Crear el enlace simbólico de storage (storage:link)?',
            default: true,
            hint: 'Necesario si tu app sirve archivos desde el disco público.'
        );

        if ($runStorageLink) {
            spin(function () {
                $this->callSilent('storage:link');
            }, 'Creando enlace simbólico de storage...');

            $this->components->info('Enlace simbólico de storage creado.');
        }

        // ── 4. CSRF Exclusion ─────────────────────────────────────────────
        $excludeCsrf = confirm(
            label: '¿Excluir rutas de Instagram de la protección CSRF?',
            default: true,
            hint: 'Necesario para recibir webhooks y callbacks OAuth de Meta. Modifica bootstrap/app.php.'
        );

        if ($excludeCsrf) {
            $this->addCsrfExclusion();
        }

        // ── 5. Rutas ──────────────────────────────────────────────────────
        $publishWebhookRoute = confirm(
            label: '¿Publicar ruta de webhook (instagram-webhook) para personalizarla?',
            default: false,
            hint: 'Copia instagram_webhook.php a routes/. Si no, se usa la ruta interna del paquete.'
        );

        if ($publishWebhookRoute) {
            spin(function () {
                $this->callSilent('vendor:publish', [
                    '--tag'   => 'instagram-webhook-routes',
                    '--force' => $this->option('force'),
                ]);
            }, 'Publicando ruta de webhook...');

            $this->components->info('Ruta de webhook publicada en routes/instagram_webhook.php');
        }

        $publishCallbackRoute = confirm(
            label: '¿Publicar ruta de callback OAuth (instagram/callback) para personalizarla?',
            default: false,
            hint: 'Copia instagram_callback.php a routes/. Si no, se usa la ruta interna del paquete.'
        );

        if ($publishCallbackRoute) {
            spin(function () {
                $this->callSilent('vendor:publish', [
                    '--tag'   => 'instagram-callback-routes',
                    '--force' => $this->option('force'),
                ]);
            }, 'Publicando ruta de callback...');

            $this->components->info('Ruta de callback publicada en routes/instagram_callback.php');
        }

        // ── 6. Canales de Broadcast (Reverb) ──────────────────────────────
        $publishChannels = confirm(
            label: '¿Publicar rutas de canales broadcast (Laravel Reverb)?',
            default: false,
            hint: 'Copia channels.php a routes/ con la autorización de canales instagram-messages.'
        );

        if ($publishChannels) {
            spin(function () {
                $this->callSilent('vendor:publish', [
                    '--tag'   => 'instagram-channels',
                    '--force' => $this->option('force'),
                ]);
            }, 'Publicando rutas de canales...');

            $this->components->info('Rutas de canales publicadas en routes/channels.php');
        }

        // ── 7. Logging ────────────────────────────────────────────────────
        note('El paquete incluye configuración de logging personalizada.');
        note('Para logs separados de Instagram, publicá manualmente:');
        $this->line('  php artisan vendor:publish --tag=instagram-logging');
        note('Esto agrega el canal "instagram" a config/logging.php.');

        // ── 8. Variables de entorno ───────────────────────────────────────
        outro('INSTALACIÓN COMPLETADA');

        $this->newLine();
        $this->line('  <options=bold>Agrega las siguientes variables a tu .env:</>');
        $this->newLine();
        $this->line('  <fg=yellow># Instagram OAuth</>');
        $this->line('  <fg=cyan>INSTAGRAM_CLIENT_ID</fg=cyan>=<tu_instagram_client_id>');
        $this->line('  <fg=cyan>INSTAGRAM_CLIENT_SECRET</fg=cyan>=<tu_instagram_client_secret>');
        $this->line('  <fg=cyan>INSTAGRAM_REDIRECT_URI</fg=cyan>=https://tu-dominio.com/instagram/callback');
        $this->newLine();
        $this->line('  <fg=yellow># Instagram API</>');
        $this->line('  <fg=cyan>INSTAGRAM_GRAPH_BASE_URL</fg=cyan>=https://graph.instagram.com');
        $this->line('  <fg=cyan>INSTAGRAM_OAUTH_BASE_URL</fg=cyan>=https://api.instagram.com');
        $this->line('  <fg=cyan>INSTAGRAM_API_VERSION</fg=cyan>=v23.0');
        $this->line('  <fg=cyan>INSTAGRAM_API_TIMEOUT</fg=cyan>=30');
        $this->line('  <fg=cyan>INSTAGRAM_API_RETRY_ATTEMPTS</fg=cyan>=3');
        $this->newLine();
        $this->line('  <fg=yellow># Instagram Webhook</>');
        $this->line('  <fg=cyan>INSTAGRAM_WEBHOOK_VERIFY_TOKEN</fg=cyan>=<tu_token_secreto>');
        $this->newLine();
        $this->line('  <fg=yellow># Instagram Broadcast (Laravel Reverb) — opcional</>');
        $this->line('  <fg=cyan>INSTAGRAM_BROADCAST_CHANNEL_TYPE</fg=cyan>=public');
        $this->line('  <fg=cyan>INSTAGRAM_CUSTOM_CHANNELS</fg=cyan>=false');
        $this->newLine();
        $this->line('  <fg=yellow># Instagram Webhook Processor — opcional</>');
        $this->line('  <fg=cyan>INSTAGRAM_WEBHOOK_PROCESSOR</fg=cyan>=\\ScriptDevelop\\InstagramApiManager\\Services\\WebhookProcessors\\BaseWebhookProcessor');
        $this->newLine();
        $this->line('  Luego visitá <fg=cyan>/instagram/connect</> para vincular una cuenta de Instagram.');
        $this->newLine();

        return self::SUCCESS;
    }

    protected function addCsrfExclusion(): void
    {
        $bootstrapPath = base_path('bootstrap/app.php');

        if (!file_exists($bootstrapPath)) {
            warning('No se encontró bootstrap/app.php. Agregá manualmente la exclusión CSRF en VerifyCsrfToken.');

            return;
        }

        $content = file_get_contents($bootstrapPath);
        $webhookPath = 'instagram-webhook/*';
        $callbackPath = 'instagram/callback';

        $alreadyExcluded = str_contains($content, $webhookPath) && str_contains($content, $callbackPath);

        if ($alreadyExcluded) {
            $this->components->info('Las rutas de Instagram ya están excluidas de CSRF.');

            return;
        }

        $paths = [];
        if (!str_contains($content, $webhookPath)) {
            $paths[] = $webhookPath;
        }
        if (!str_contains($content, $callbackPath)) {
            $paths[] = $callbackPath;
        }

        $pathsStr = "'" . implode("', '", $paths) . "'";

        if (str_contains($content, 'validateCsrfTokens')) {
            $updated = preg_replace(
                '/(validateCsrfTokens\s*\(\s*except\s*:\s*\[)(\n?\s*|\s*)/',
                "$1\n            {$pathsStr}, ",
                $content,
                1
            );

            if ($updated !== null && $updated !== $content) {
                if (file_put_contents($bootstrapPath, $updated) !== false) {
                    $this->components->info("Rutas {$pathsStr} agregadas a la exclusión CSRF existente.");

                    return;
                }
            }

            warning('No se pudo modificar la exclusión CSRF. Agregá manualmente:');
            $this->line("  \$middleware->validateCsrfTokens(except: [{$pathsStr}, ...]);");

            return;
        }

        $pattern = '/->withMiddleware\s*\(\s*function\s*\(\s*(?:Middleware\s+)?\$middleware\s*\)\s*\{/';
        $replacement = '$0' . PHP_EOL . "        \$middleware->validateCsrfTokens(except: [" . PHP_EOL . "            {$pathsStr}," . PHP_EOL . '        ]);';

        $updated = preg_replace($pattern, $replacement, $content, 1, $count);

        if ($count && $updated !== null) {
            file_put_contents($bootstrapPath, $updated);
            $this->components->info("Rutas {$pathsStr} excluidas de CSRF en bootstrap/app.php");
        } else {
            warning('No se pudo modificar bootstrap/app.php automáticamente. Agregá manualmente:');
            $this->line('  En App\Http\Middleware\VerifyCsrfToken agregá:');
            $this->line("  protected \$except = [{$pathsStr}];");
        }
    }
}
