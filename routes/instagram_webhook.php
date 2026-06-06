<?php

use Illuminate\Support\Facades\Route;
use ScriptDevelop\InstagramApiManager\Http\Controllers\InstagramWebhookController;

$igLimit = config('instagram.rate_limit.max_attempts', 60) . ',' . config('instagram.rate_limit.decay_minutes', 1);

Route::prefix('instagram-webhook')->group(function () use ($igLimit) {
    Route::get('/', [InstagramWebhookController::class, 'handle'])
        ->middleware('throttle:10,1')
        ->name('instagram.webhook.verify');
    Route::post('/', [InstagramWebhookController::class, 'handle'])
        ->middleware("throttle:{$igLimit}")
        ->name('instagram.webhook.handle');
});
