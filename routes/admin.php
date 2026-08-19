<?php

use App\Http\Controllers\Admin\AdminAmenityController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AdminCityController;
use App\Http\Controllers\Admin\AdminCurrencySettingController;
use App\Http\Controllers\Admin\AdminEmergencyNumberController;
use App\Http\Controllers\Admin\AdminExchangeRateController;
use App\Http\Controllers\Admin\AdminGuideRequestController;
use App\Http\Controllers\Admin\AdminHotelController;
use App\Http\Controllers\Admin\AdminPackageController;
use App\Http\Controllers\Admin\AdminPriceController;
use App\Http\Controllers\Admin\AdminRegionController;
use App\Http\Controllers\Admin\AdminRoomController;
use App\Http\Controllers\Admin\AdminSeasonController;
use App\Http\Controllers\Admin\AdminTagController;
use App\Http\Controllers\Admin\AdminTouristGuideController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Mutual\PictureController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['web', 'check_api_password', 'check_language'])->group(function () {
    Route::post('login', [AdminAuthController::class, 'login'])->middleware('throttle:login');
    Route::post('logout', [AdminAuthController::class, 'logout'])->middleware(['web', 'auth:sanctum', 'admin']);

    Route::middleware(['web', 'auth:sanctum', 'admin'])->group(function () {
        Route::apiResource('hotels', AdminHotelController::class);
        Route::get('hotels/{hotel}/amenities', [AdminHotelController::class, 'amenities']);
        Route::get('/hotels/{hotel}/rooms', [AdminRoomController::class, 'index']);
        Route::get('/hotels/{hotel}/ratings', [RatingController::class, 'indexForHotel']);
        Route::get('/hotels/{hotel}/pictures', [PictureController::class, 'hotel']);
        Route::post('hotels/{hotel}/pictures', [AdminHotelController::class, 'updatePictures']);
        Route::post('hotels/{hotel}/thumbnails', [AdminHotelController::class, 'updateThumbnails']);

        Route::apiResource('cities', AdminCityController::class);
        Route::get('/cities/{city}/pictures', [PictureController::class, 'city']);
        Route::post('cities/{city}/pictures', [AdminCityController::class, 'updatePictures']);
        Route::post('cities/{city}/thumbnails', [AdminCityController::class, 'updateThumbnails']);
        Route::get('/cities/{city}/regions', [AdminCityController::class, 'regions']);
        Route::get('/cities/{city}/hotels', [AdminCityController::class, 'hotels']);
        Route::get('/cities/{city}/ratings', [RatingController::class, 'indexForCity']);

        Route::apiResource('regions', AdminRegionController::class);
        Route::get('/regions/{region}/pictures', [PictureController::class, 'region']);
        Route::post('regions/{region}/pictures', [AdminRegionController::class, 'updatePictures']);
        Route::post('regions/{region}/thumbnails', [AdminRegionController::class, 'updateThumbnails']);
        Route::get('/regions/{region}/ratings', [RatingController::class, 'indexForRegion']);

        Route::apiResource('packages', AdminPackageController::class);
        Route::get('packages/{package}/pictures', [PictureController::class, 'package']);
        Route::post('packages/{package}/pictures', [AdminPackageController::class, 'updatePictures']);
        Route::post('packages/{package}/thumbnails', [AdminPackageController::class, 'updateThumbnails']);

        Route::get('rooms/{room}', [AdminRoomController::class, 'show']);
        Route::get('rooms/{room}/amenities', [AdminRoomController::class, 'amenities']);
        Route::get('/rooms/{room}/ratings', [RatingController::class, 'indexForRoom']);
        Route::post('hotels/{hotel}/rooms', [AdminRoomController::class, 'store']);
        Route::put('/rooms/{room}', [AdminRoomController::class, 'update']);
        Route::delete('/rooms/{room}', [AdminRoomController::class, 'destroy']);

        Route::post('amenities', [AdminAmenityController::class, 'store']);
        Route::put('amenities/{amenity}', [AdminAmenityController::class, 'update']);
        Route::delete('amenities/{amenity}', [AdminAmenityController::class, 'destroy']);

        Route::apiResource('tags', AdminTagController::class)->only(['store', 'update', 'destroy', 'index', 'show']);

        Route::get('seasons/current', [AdminSeasonController::class, 'current'])->name('seasons.current');
        Route::post('seasons/clear-caches', [AdminSeasonController::class, 'clearCaches'])->name('seasons.clear-caches');
        Route::apiResource('seasons', AdminSeasonController::class);

        Route::get('exchange-rates/clear-caches', [AdminExchangeRateController::class, 'clearCaches'])->name('exchange-rates.clear-caches');
        Route::post('exchange-rates/bulk', [AdminExchangeRateController::class, 'bulkUpsert'])->name('exchange-rates.bulk');
        Route::apiResource('exchange-rates', AdminExchangeRateController::class)->only(['store', 'update', 'destroy', 'index', 'show']);

        Route::get('currency-settings', [AdminCurrencySettingController::class, 'index'])->name('currency-settings.index');
        Route::put('currency-settings', [AdminCurrencySettingController::class, 'update'])->name('currency-settings.update');

        Route::get('prices/clear-caches', [AdminPriceController::class, 'clearCaches'])->name('prices.clear-caches');
        Route::post('prices/bulk', [AdminPriceController::class, 'bulkUpsert'])->name('prices.bulk');
        Route::apiResource('prices', AdminPriceController::class)->only(['store', 'update', 'destroy', 'index', 'show']);

        Route::apiResource('tourist-guides', AdminTouristGuideController::class);
        Route::get('tourist-guides/{touristGuide}/ratings', [RatingController::class, 'indexForGuide'])->name('tourist-guides.ratings');

        Route::get('guide-requests', [AdminGuideRequestController::class, 'index'])->name('guide-requests.index');
        Route::get('guide-requests/{guideRequest}', [AdminGuideRequestController::class, 'show'])->name('guide-requests.show');
        Route::post('guide-requests/{guideRequest}/approve', [AdminGuideRequestController::class, 'approve'])->name('guide-requests.approve');
        Route::post('guide-requests/{guideRequest}/reject', [AdminGuideRequestController::class, 'reject'])->name('guide-requests.reject');

        Route::get('bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
        Route::get('bookings/{booking}', [AdminBookingController::class, 'show'])->name('bookings.show');
        Route::post('bookings/{booking}/approve', [AdminBookingController::class, 'approve'])->name('bookings.approve');
        Route::post('bookings/{booking}/reject', [AdminBookingController::class, 'reject'])->name('bookings.reject');

        Route::apiResource('emergency-numbers', AdminEmergencyNumberController::class);
    });
});
