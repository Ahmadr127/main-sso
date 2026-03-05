<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;

// SSO API: endpoint untuk sistem klien mengambil data user setelah mendapat access token
Route::middleware('auth:api')->group(function () {
    Route::get('/user', [UserController::class, 'show']);
});
