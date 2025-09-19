<?php

use App\Http\Controllers\UrlController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// URL Redirect Route (must be last to catch all short codes)
Route::get('/{code}', [UrlController::class, 'redirect']);
