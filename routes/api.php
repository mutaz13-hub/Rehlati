<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ForgotPasswordController;
use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\Mutual\{
    HotelController,
    RoomController,
    AmenityController,
    CityController,
    RegionController,
    PictureController,
    TagController
};
use App\Models\{
    City,
    Hotel,
    Room,
    Region
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
       
  

    // Public (user + admin) hotel listings and details
    Route::get('/hotels', [HotelController::class, 'index'])->name('hotels.index');
    Route::get('/hotels/{hotel}', [HotelController::class, 'show'])->name('hotels.show');
    Route::get('/hotels/{hotel}/pictures', [PictureController::class, 'hotel']);
    Route::get('/hotels/{hotel}/ratings', [RatingController::class, 'indexForHotel']);
    Route::get('/hotels/{hotel}/amenities', [HotelController::class, 'amenities']);
    // Public room listings and details
    Route::get('/hotels/{hotel}/rooms', [RoomController::class, 'index']);
    Route::get('/rooms/{room}', [RoomController::class, 'show']);
    Route::get('/rooms/{room}/amenities', [RoomController::class, 'amenities']);
    Route::get('/rooms/{room}/ratings', [RatingController::class, 'indexForRoom']);

    // Public city listings and details
    Route::apiResource('/cities', CityController::class);
    Route::get('/cities/{city}/pictures', [PictureController::class, 'city']);
    Route::get('/cities/{city}/regions', [CityController::class, 'regions']);
    Route::get('/cities/{city}/hotels', [CityController::class, 'hotels']);
    // Route::get('/cities', [CityController::class, 'index']);
    // Route::get('/cities/{city}', [CityController::class, 'show']);
    Route::get('/cities/{city}/ratings', [RatingController::class, 'indexForCity']);
    Route::get('/regions', [RegionController::class, 'index'])->name('regions.index');
    Route::get('/regions/{region}', [RegionController::class, 'show'])->name('regions.show');
    Route::get('/regions/{region}/pictures', [PictureController::class, 'region']);
    Route::get('/regions/{region}/ratings', [RatingController::class, 'indexForRegion']);

    // Ratings
        Route::post('/ratings/cities/{id}', [RatingController::class, 'store'])->name('ratings.cities');
        Route::post('/ratings/hotels/{id}', [RatingController::class, 'store'])->name('ratings.hotels');
        Route::post('/ratings/rooms/{id}', [RatingController::class, 'store'])->name('ratings.rooms');
        Route::post('/ratings/regions/{id}', [RatingController::class, 'store'])->name('ratings.regions');
        Route::post('/ratings/car_agencies/{id}', [RatingController::class, 'store'])->name('ratings.car_agencies');
        
        Route::patch('/ratings/{rating}', [RatingController::class, 'update']);
        Route::delete('/ratings/{rating}', [RatingController::class, 'destroy']);
        Route::post('/ratings/{rating}/vote', [RatingController::class, 'vote']);
  
    Route::get('/ratings/{rating}', [RatingController::class, 'show']);
    // Public amenities and tags
    Route::get('/amenities', [AmenityController::class, 'index']);
    Route::get('/amenities/{amenity}', [AmenityController::class, 'show']);
    // Route::get('/tags', [TagController::class, 'index']);
    // Route::get('/tags/{tag}', [TagController::class, 'show']);
      });
});

});
