<?php

use Illuminate\Support\Facades\Route;
use ScriptDevelop\InstagramApiManager\Http\Controllers\MessengerWebhookController;

$fbLimit = config('facebook.rate_limit.max_attempts', 60) . ',' . config('facebook.rate_limit.decay_minutes', 1);

Route::prefix('facebook-webhook')->group(function () use ($fbLimit) {
    Route::get('/', [MessengerWebhookController::class, 'handle'])
        ->middleware('throttle:10,1')
        ->name('facebook.webhook.verify');
    Route::post('/', [MessengerWebhookController::class, 'handle'])
        ->middleware("throttle:{$fbLimit}")
        ->name('facebook.webhook.handle');
});
