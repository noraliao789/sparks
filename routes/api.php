<?php

use App\Http\Controllers\Api\V1\Auth\GoogleController;
use App\Http\Controllers\Api\V1\Auth\LineController;
use App\Http\Controllers\Api\V1\Me\VerificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('google/redirect', [GoogleController::class, 'redirectUrl']);
        Route::get('google/callback', [GoogleController::class, 'handleCallback']);
        Route::get('line/redirect', [LineController::class, 'redirectUrl']);
        Route::get('line/callback', [LineController::class, 'handleCallback']);
        // Link LINE Login：需要 token（會員中心綁定登入方式）
        Route::middleware('api.auth')->group(function () {
            Route::get('line/link/redirect', [LineController::class, 'linkRedirect']);
            Route::get('google/link/redirect', [GoogleController::class, 'linkRedirect']);
        });
        Route::get('line/link/callback', [LineController::class, 'linkCallback']);
        Route::get('google/link/callback', [GoogleController::class, 'linkCallback']);
    });
    Route::middleware('api.auth')->group(function () {
        Route::post('me/verification', [VerificationController::class, 'status']);
        Route::post('me/verification/otp/send', [VerificationController::class, 'sendOtp']);
        Route::post('me/verification/otp/verify', [VerificationController::class, 'verifyOtp']);
        Route::prefix('events')->group(function () {
            Route::post('index', [\App\Http\Controllers\Api\V1\EventController::class, 'index']);
            Route::post('create', [\App\Http\Controllers\Api\V1\EventController::class, 'create']);
            Route::post('apply', [\App\Http\Controllers\Api\V1\EventController::class, 'apply']);
            Route::post('join', [\App\Http\Controllers\Api\V1\EventController::class, 'join']);
        });
    });
    Route::middleware(['api.auth', 'throttle:chat-send'])->post(
        'events/messages',
        [\App\Http\Controllers\Api\V1\EventController::class, 'send']
    );
});