<?php

use App\Http\Controllers\UrlController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// URL Shortening API Routes
Route::post('/shorten', [UrlController::class, 'shorten']);
Route::get('/{code}/stats', [UrlController::class, 'stats']);
