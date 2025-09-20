<?php

use App\Http\Controllers\UrlController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// URL Shortening API Routes with throttling (60 requests per minute)
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/shorten', [UrlController::class, 'shorten']);
    Route::get('/{code}/stats', [UrlController::class, 'stats']);
});

// Production API Routes with HMAC authentication (higher rate limits)
Route::middleware(['throttle:300,1', 'hmac.auth'])->group(function () {
    Route::post('/v1/shorten', [UrlController::class, 'shorten']);
    Route::get('/v1/{code}/stats', [UrlController::class, 'stats']);
});
