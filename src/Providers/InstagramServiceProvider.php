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
        // Registrar configuración u otros bindings
        // $this->mergeConfigFrom(__DIR__.'/../../config/instagram.php', 'instagram');
        $this->mergeConfigFrom(__DIR__.'/../../config/instagram.php', 'instagram');
    }

    /**
     * Bootstrap de servicios, publicación de recursos, rutas, vistas, etc.
     */
    public function boot(): void
    {
        // Publicar migraciones del paquete
        $this->publishes([
            __DIR__.'/../../database/migrations/' => database_path('migrations'),
        ], 'instagram-migrations');

        $this->publishes([
            __DIR__.'/../../config/instagram.php' => config_path('instagram.php'),
        ], 'instagram-config');

        // Si tienes config, vistas o rutas, se agregan aquí

        // $this->publishes([
        //     __DIR__.'/../../config/instagram.php' => config_path('instagram.php'),
        // ], 'instagram-config');

        // $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        // $this->loadViewsFrom(__DIR__.'/../../resources/views', 'instagram');
    }
}
