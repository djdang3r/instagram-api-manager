<?php

use Illuminate\Support\Facades\Route;
use ScriptDevelop\InstagramApiManager\Http\Controllers\InstagramWebhookController;

Route::prefix('instagram-webhook')->group(function () {
    Route::match(['get', 'post'], '/', [InstagramWebhookController::class, 'handle'])->name('instagram.webhook.handle');
});
