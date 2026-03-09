<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;

Route::post('/login', [AuthController::class, 'login']);

// SSO API: endpoint untuk sistem klien mengambil data user setelah mendapat access token
Route::middleware('auth:api')->group(function () {
    Route::get('/user', [UserController::class, 'show']);
});
