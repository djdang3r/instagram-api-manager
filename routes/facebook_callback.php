<?php

use Illuminate\Support\Facades\Route;
use ScriptDevelop\InstagramApiManager\Http\Controllers\Auth\FacebookAuthController;

Route::get('/facebook/callback', [FacebookAuthController::class, 'callback'])->name('facebook.auth.callback');
Route::get('/facebook/connect', [FacebookAuthController::class, 'connect'])->name('facebook.connect');