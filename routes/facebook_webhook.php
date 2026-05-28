<?php

use Illuminate\Support\Facades\Route;
use ScriptDevelop\InstagramApiManager\Http\Controllers\MessengerWebhookController;

// Registrar ruta del webhook de Facebook Messenger (interna, sin publicar)
Route::prefix('facebook-webhook')->middleware('throttle:60,1')->group(function () {
    Route::match(['get', 'post'], '/', [MessengerWebhookController::class, 'handle'])
        ->name('facebook.webhook.handle');
});