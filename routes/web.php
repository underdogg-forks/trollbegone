<?php

use App\Http\Controllers\InstagramOAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Instagram OAuth routes
Route::prefix('auth/instagram')->middleware(['auth'])->group(function () {
    Route::get('/redirect', [InstagramOAuthController::class, 'redirectToProvider'])
        ->name('instagram.oauth.redirect');
    
    Route::get('/callback', [InstagramOAuthController::class, 'handleProviderCallback'])
        ->name('instagram.oauth.callback');
    
    Route::post('/disconnect/{account}', [InstagramOAuthController::class, 'disconnect'])
        ->name('instagram.oauth.disconnect');
});
