<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleController;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/up', function () {
    return response()->json(['status' => 'ok', 'system' => 'IEEPIS', 'version' => '1.0.0']);
});

Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);
