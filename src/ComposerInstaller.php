<?php

namespace ScriptDevelop\InstagramApiManager;

use Composer\Script\Event;

class ComposerInstaller
{
    public static function postInstall(Event $event): void
    {
        $io = $event->getIO();

        $package = $event->getComposer()->getPackage();
        if ($package->getName() !== 'scriptdevelop/instagram-api-manager') {
            return;
        }

        $io->write('  <bg=green;fg=white> SUCCESS </> <fg=green>Instagram API Manager instalado correctamente.</>');
        $io->write('');

        $io->write('  <options=bold>Siguientes Pasos:</>');
        $io->write('  <fg=yellow>1. Ejecuta el asistente de instalación:</>');
        $io->write('     <fg=cyan>php artisan instagram:install</>');
        $io->write('');
        $io->write('  <fg=yellow>2. O publica la configuración y migraciones manualmente:</>');
        $io->write('     <fg=cyan>php artisan vendor:publish --tag=instagram-config</>');
        $io->write('     <fg=cyan>php artisan vendor:publish --tag=instagram-facebook-config</>');
        $io->write('     <fg=cyan>php artisan vendor:publish --tag=instagram-migrations</>');
        $io->write('     <fg=cyan>php artisan migrate</>');
        $io->write('');
        $io->write('  <fg=yellow>3. Agrega las siguientes variables a tu .env:</>');
        $io->write('     <fg=cyan>INSTAGRAM_CLIENT_ID=tu_instagram_client_id</>');
        $io->write('     <fg=cyan>INSTAGRAM_CLIENT_SECRET=tu_instagram_client_secret</>');
        $io->write('     <fg=cyan>INSTAGRAM_REDIRECT_URI=https://tu-dominio.com/instagram/callback</>');
        $io->write('     <fg=cyan>INSTAGRAM_WEBHOOK_VERIFY_TOKEN=tu_token_secreto</>');
        $io->write('');
    }
}
