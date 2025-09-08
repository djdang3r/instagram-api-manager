<?php

namespace ScriptDevelop\InstagramApiManager\Providers;

use Illuminate\Support\ServiceProvider;
use ScriptDevelop\InstagramApiManager\Services\InstagramAccountService;
use ScriptDevelop\InstagramApiManager\Services\InstagramMessageService;
use ScriptDevelop\InstagramApiManager\Services\FacebookAccountService;
use ScriptDevelop\InstagramApiManager\Services\FacebookMessageService;

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

        $this->app->singleton('instagram.account', function ($app) {
            return new InstagramAccountService();
        });

        $this->app->singleton('instagram.message', function ($app) {
            return new InstagramMessageService();
        });

        $this->app->singleton('facebook.account', function () {
            return new FacebookAccountService();
        });

        $this->app->singleton('facebook.message', function () {
            return new FacebookMessageService();
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

        // Publicar archivo de ruta para que el usuario pueda copiar y modificar si quiere
        $this->publishes([
            __DIR__ . '/../../routes/instagram_callback.php' => base_path('routes/instagram_callback.php'),
        ], 'instagram-callback-routes');

        // Publicar configuración para logging personalizado (tag: instagram-logging)
        $this->publishes([
            __DIR__ . '/../../config/logging-additions.php' => config_path('logging-additions.php'),
        ], 'instagram-logging');

        // Publicación completa (todo junto) para simplicidad (tag: instagram-api-manager)
        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations'),
            __DIR__ . '/../../config/instagram.php' => config_path('instagram.php'),
            __DIR__ . '/../../config/facebook.php' => config_path('facebook.php'),
            __DIR__ . '/../../routes/instagram_webhook.php' => base_path('routes/instagram_webhook.php'),
            __DIR__ . '/../../config/logging-additions.php' => config_path('logging-additions.php'),
        ], 'instagram-api-manager');

        // Registrar el comando Artisan para refrescar tokens largos
        $this->commands([
            \ScriptDevelop\InstagramApiManager\Console\Commands\RefreshInstagramTokens::class,
            \ScriptDevelop\InstagramApiManager\Console\Commands\CleanupOauthStates::class,
            \ScriptDevelop\InstagramApiManager\Console\Commands\ProcessPendingMessages::class,
            \ScriptDevelop\InstagramApiManager\Console\Commands\SyncInstagramConversations::class,
        ]);

        // Puedes cargar vistas o comandos si el paquete los tuviera aquí
        // $this->loadViewsFrom(__DIR__.'/../../resources/views', 'instagram');
        // $this->commands([
        //     YourCommandClass::class,
        // ]);
    }
}
