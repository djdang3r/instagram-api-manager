<?php

namespace ScriptDevelop\InstagramApiManager\Providers;

use Illuminate\Support\ServiceProvider;

class InstagramServiceProvider extends ServiceProvider
{
    /**
     * Registrar servicios y bindings en el contenedor de Laravel.
     */
    public function register(): void
    {
        // Registrar configuración del paquete para que se pueda acceder con config('instagram')
        $this->mergeConfigFrom(__DIR__ . '/../../config/instagram.php', 'instagram');

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

        // Publicar archivo de configuración (tag: instagram-config)
        $this->publishes([
            __DIR__ . '/../../config/instagram.php' => config_path('instagram.php'),
        ], 'instagram-config');

        // Cargar y publicar rutas del webhook Instagram
        $this->loadRoutesFrom(__DIR__ . '/../../routes/instagram_webhook.php');
        $this->publishes([
            __DIR__ . '/../../routes/instagram_webhook.php' => base_path('routes/instagram_webhook.php'),
        ], 'instagram-webhook-routes');

        // Publicar configuración para logging personalizado (tag: instagram-logging)
        $this->publishes([
            __DIR__ . '/../../config/logging-additions.php' => config_path('logging-additions.php'),
        ], 'instagram-logging');

        // PUBLICACIÓN COMPLETA (TODO JUNTO) para simplicidad (tag: instagram-api-manager)
        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations'),
            __DIR__ . '/../../config/instagram.php' => config_path('instagram.php'),
            __DIR__ . '/../../routes/instagram_webhook.php' => base_path('routes/instagram_webhook.php'),
            __DIR__ . '/../../config/logging-additions.php' => config_path('logging-additions.php'),
        ], 'instagram-api-manager');

        // Puedes cargar vistas o comandos si el paquete los tuviera aquí
        // $this->loadViewsFrom(__DIR__.'/../../resources/views', 'instagram');
        // $this->commands([
        //     YourCommandClass::class,
        // ]);
    }
}
