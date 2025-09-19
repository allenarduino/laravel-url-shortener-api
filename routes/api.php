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
