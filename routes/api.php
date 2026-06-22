<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ForgotPasswordController;
use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\Mutual\{
    HotelController,
    RoomController,
    RoomTypeController,
    AmenityController,
    CityController,
    RegionController
};
use App\Http\Controllers\Api\RatingController;

Route::middleware(['check_api_password', 'check_language'])->group(function () {

 require __DIR__.'/admin.php';
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware(['ensure_can_refresh', 'throttle:refresh']);
    
    Route::middleware('check_guest')->group(function () {
        Route::controller(AuthController::class)->group(function () {
            Route::post('/register', 'register')->middleware('throttle:register');
            Route::post('/login', 'login')->middleware('throttle:login');
            Route::post('/google-login', 'google_login')->middleware('throttle:google-login');
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
  

    // Public (user + admin) hotel listings and details
    Route::get('/hotels', [HotelController::class, 'index']);
    Route::get('/hotels/{hotel}', [HotelController::class, 'show']);
    Route::get('/hotels/{hotel}/ratings', [RatingController::class, 'indexForHotel']);
    // Public room listings and details
    Route::get('/hotels/{hotel}/rooms', [RoomController::class, 'index']);
    Route::get('/rooms/{room}', [RoomController::class, 'show']);
    Route::get('/rooms/{room}/ratings', [RatingController::class, 'indexForRoom']);

    // Public city listings and details
    Route::apiResource('/cities', CityController::class);
    // Route::get('/cities', [CityController::class, 'index']);
    // Route::get('/cities/{city}', [CityController::class, 'show']);
    Route::get('/cities/{city}/ratings', [RatingController::class, 'indexForCity']);
    Route::get('/regions', [RegionController::class, 'index']);
    Route::get('/regions/{region}', [RegionController::class, 'show']);
    Route::get('/regions/{region}/ratings', [RatingController::class, 'indexForRegion']);

    // Ratings
    Route::apiResource('/ratings', RatingController::class)->except('index');
    Route::post('/ratings/{rating}/vote', [RatingController::class, 'vote']);
    // Public room-types and amenities
    Route::get('/room-types', [RoomTypeController::class, 'index']);
    Route::get('/room-types/{roomType}', [RoomTypeController::class, 'show']);
    Route::get('/amenities', [AmenityController::class, 'index']);
    Route::get('/amenities/{amenity}', [AmenityController::class, 'show']);
      });
});
