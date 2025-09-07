<?php

use Illuminate\Support\Facades\Route;
use ScriptDevelop\InstagramApiManager\Http\Controllers\Auth\InstagramAuthController;
use ScriptDevelop\InstagramApiManager\Http\Controllers\Auth\FacebookAuthController;

Route::get('/instagram/callback', [InstagramAuthController::class, 'callback'])->name('instagram.auth.callback');
Route::get('/facebook/callback', [FacebookAuthController::class, 'callback'])->name('facebook.auth.callback');

Route::get('/instagram/connect', [InstagramAuthController::class, 'connect'])->name('instagram.connect');