<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ForgotPasswordController;
use Illuminate\Support\Facades\Route;

Route::middleware(['check_api_password', 'check_language'])->group(function () {
    Route::middleware('check_guest')->group(function () {
        Route::controller(AuthController::class)->group(function () {
            Route::post('/register', 'register')->middleware('throttle:register');
            Route::post('/login', 'login')->middleware('throttle:login');
        });

        Route::controller(ForgotPasswordController::class)->group(function () {
            Route::post('/forgot-password', 'forgot_password')->middleware('throttle:forgot-password');
            Route::post('/validate-reset-code', 'validate_reset_code')->middleware('throttle:validate-reset-code');
            Route::post('/reset-password', 'reset_password')->middleware('throttle:reset-password');
        });
    });

    Route::middleware(['auth:sanctum', 'check_access_token_device'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::middleware('check_email_not_verified')->controller(AuthController::class)->group(function () {
            Route::post('/verify-email', 'verify_email')->middleware('throttle:verify-email');
            Route::post('/resend-verification-code', 'resend_verification_code')->middleware('throttle:resend-verification-code');
        });

        Route::middleware('check_email_verified')->controller(AuthController::class)->group(function () {
            Route::post('/logout-other-devices', 'logout_other_devices');
            Route::post('/logout-all-devices', 'logout_all_devices');
        });
    });
});
