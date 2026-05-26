<?php

namespace ScriptDevelop\InstagramApiManager\Providers;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use ScriptDevelop\InstagramApiManager\Contracts\WebhookProcessorInterface;
use ScriptDevelop\InstagramApiManager\Http\Controllers\MessengerWebhookController;
use ScriptDevelop\InstagramApiManager\InstagramApi\ApiClient;
use ScriptDevelop\InstagramApiManager\Services\InstagramAccountService;
use ScriptDevelop\InstagramApiManager\Services\InstagramMessageService;
use ScriptDevelop\InstagramApiManager\Services\FacebookAccountService;
use ScriptDevelop\InstagramApiManager\Services\FacebookMessageService;
use ScriptDevelop\InstagramApiManager\Services\MessengerMessageService;
use ScriptDevelop\InstagramApiManager\Services\InstagramPersistentMenuService;
use ScriptDevelop\InstagramApiManager\Services\InstagramLinkService;
use ScriptDevelop\InstagramApiManager\Services\WebhookProcessors\BaseWebhookProcessor;

class InstagramServiceProvider extends ServiceProvider
{
    /**
     * Registrar servicios y bindings en el contenedor de Laravel.
     */
    public function register(): void
    {
        // Registrar configuración del paquete para que se pueda acceder con config('instagram')
        $this->mergeConfigFrom(__DIR__ . '/../../config/instagram.php', 'instagram');
        $this->mergeConfigFrom(__DIR__ . '/../../config/facebook.php', 'facebook');

        // Shared Guzzle HTTP client (singleton) — one connection pool for all ApiClient instances
        $this->app->singleton(Client::class, function ($app) {
            return new Client([
                'timeout' => (int) config('instagram.api.timeout', 30),
                'connect_timeout' => 10,
            ]);
        });

        // Transient ApiClient binding — each resolution gets its own instance with its own baseUrl/version
        $this->app->bind(ApiClient::class, function ($app) {
            return new ApiClient(
                config('instagram.api.graph_base_url', 'https://graph.facebook.com'),
                config('instagram.api.version'),
                (int) config('instagram.api.timeout', 30),
                $app->make(Client::class)
            );
        });

        $this->app->singleton('instagram.account', function ($app) {
            return new InstagramAccountService();
        });

        $this->app->singleton('instagram.message', function ($app) {
            return new InstagramMessageService();
        });

        $this->app->singleton('instagram.persistent_menu', function ($app) {
            return new InstagramPersistentMenuService();
        });

        $this->app->singleton('instagram.link', function ($app) {
            return new InstagramLinkService();
        });

        $this->app->singleton('facebook.account', function () {
            return new FacebookAccountService();
        });

        $this->app->singleton('facebook.message', function () {
            return new FacebookMessageService();
        });

        $this->app->singleton('messenger.message', function ($app) {
            return new MessengerMessageService();
        });

        // Registrar binding para Facade 'instagram' en caso de uso directo
        $this->app->singleton('instagram', function ($app) {
            return new class {
                public function account()
                {
                    return app('instagram.account');
                }
                public function message()
                {
                    return app('instagram.message');
                }
                public function persistentMenu()
                {
                    return app('instagram.persistent_menu');
                }
            };
        });

        $this->app->singleton('facebook', function () {
            return new class {
                public function account()
                {
                    return app('facebook.account');
                }
                public function message()
                {
                    return app('facebook.message');
                }
            };
        });

        // También puedes registrar otras configuraciones o bindings si es necesario

        // Registrar el procesador de webhook con valor por defecto
        $this->app->bind(
            WebhookProcessorInterface::class,
            function ($app) {
                $processorClass = config(
                    'instagram.webhook.processor',
                    BaseWebhookProcessor::class
                );

                if (class_exists($processorClass)) {
                    return $app->make($processorClass);
                }

                return $app->make(BaseWebhookProcessor::class);
            }
        );
    }

    /**
     * Bootstrap de servicios, publicación de recursos, rutas, vistas, etc.
     */
    public function boot(): void
    {
        // Cargar migraciones directamente desde el paquete para que se ejecuten con php artisan migrate sin publicar
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // PUBLICACIÓN DE RECURSOS CON TAGS PARA FACILITAR LA PUBLICACIÓN

        // Publicar migraciones del paquete (tag: instagram-migrations)
        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations'),
        ], 'instagram-migrations');

         // Publicar configuraciones del paquete (tag: instagram-config)
        $this->publishes([
            __DIR__ . '/../../config/instagram.php' => config_path('instagram.php'),
            __DIR__ . '/../../config/facebook.php' => config_path('facebook.php'),
        ], 'instagram-facebook-config');

        // Cargar y publicar rutas del webhook Instagram
        $this->loadRoutesFrom(__DIR__ . '/../../routes/instagram_webhook.php');
        $this->publishes([
            __DIR__ . '/../../routes/instagram_webhook.php' => base_path('routes/instagram_webhook.php'),
        ], 'instagram-webhook-routes');

        // Cargar ruta internamente para que funcione sin publicar
        $this->loadRoutesFrom(__DIR__ . '/../../routes/instagram_callback.php');

        // Registrar ruta del webhook de Facebook Messenger (interna, sin publicar)
        Route::prefix('facebook-webhook')->middleware('throttle:60,1')->group(function () {
            Route::match(['get', 'post'], '/', [MessengerWebhookController::class, 'handle'])
                ->name('facebook.webhook.handle');
        });

        // Publicar archivo de ruta para que el usuario pueda copiar y modificar si quiere
        $this->publishes([
            __DIR__ . '/../../routes/instagram_callback.php' => base_path('routes/instagram_callback.php'),
        ], 'instagram-callback-routes');

        // Cargar rutas de canales de broadcast (Reverb) si custom_channels está desactivado
        $channelsPath = __DIR__ . '/../../routes/channels.php';
        if (!config('instagram.broadcast.custom_channels', false) && file_exists($channelsPath)) {
            $this->loadRoutesFrom($channelsPath);
        }

        // Publicar rutas de canales de broadcast (tag: instagram-channels)
        if (file_exists($channelsPath)) {
            $this->publishes([
                $channelsPath => base_path('routes/channels.php'),
            ], 'instagram-channels');
        }

        // Publicar configuración para logging personalizado (tag: instagram-logging)
        $this->publishes([
            __DIR__ . '/../../config/logging-additions.php' => config_path('logging-additions.php'),
        ], 'instagram-logging');

        // Publicación completa (todo junto) para simplicidad (tag: instagram-api-manager)
        $publishAll = [
            __DIR__ . '/../../database/migrations/' => database_path('migrations'),
            __DIR__ . '/../../config/instagram.php' => config_path('instagram.php'),
            __DIR__ . '/../../config/facebook.php' => config_path('facebook.php'),
            __DIR__ . '/../../routes/instagram_webhook.php' => base_path('routes/instagram_webhook.php'),
            __DIR__ . '/../../config/logging-additions.php' => config_path('logging-additions.php'),
        ];
        if (file_exists($channelsPath)) {
            $publishAll[$channelsPath] = base_path('routes/channels.php');
        }
        $this->publishes($publishAll, 'instagram-api-manager');

        // Registrar el comando Artisan para refrescar tokens largos
        $this->commands([
            \ScriptDevelop\InstagramApiManager\Console\Commands\RefreshInstagramTokens::class,
            \ScriptDevelop\InstagramApiManager\Console\Commands\CleanupOauthStates::class,
            \ScriptDevelop\InstagramApiManager\Console\Commands\ProcessPendingMessages::class,
            \ScriptDevelop\InstagramApiManager\Console\Commands\SyncInstagramConversations::class,
            \ScriptDevelop\InstagramApiManager\Console\Commands\InstallInstagramApiManager::class,
            \ScriptDevelop\InstagramApiManager\Console\Commands\RefreshMessengerTokens::class,
            \ScriptDevelop\InstagramApiManager\Console\Commands\SyncMessengerConversations::class,
        ]);

        // Puedes cargar vistas o comandos si el paquete los tuviera aquí
        // $this->loadViewsFrom(__DIR__.'/../../resources/views', 'instagram');
        // $this->commands([
        //     YourCommandClass::class,
        // ]);
    }
}
