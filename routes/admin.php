<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Admin\AdminHotelController;
use App\Http\Controllers\Admin\AdminRoomController;
use App\Http\Controllers\Admin\AdminAmenityController;
use App\Http\Controllers\Admin\AdminCityController;
use App\Http\Controllers\Admin\AdminRegionController;
use App\Http\Controllers\Admin\AdminTagController;
use App\Http\Controllers\Mutual\PictureController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['web', 'check_api_password', 'check_language'])->group(function () {
    Route::post('login', [AdminAuthController::class, 'login'])->middleware('throttle:login');
    Route::post('logout', [AdminAuthController::class, 'logout'])->middleware(['web', 'auth:sanctum', 'admin']);

    Route::middleware(['web', 'auth:sanctum', 'admin'])->group(function () {
    Route::apiResource('hotels', AdminHotelController::class);
    Route::get('hotels/{hotel}/amenities', [AdminHotelController::class, 'amenities']);
    Route::get('hotels/{hotel}/rooms', [AdminRoomController::class, 'index']);
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

    Route::get('rooms/{room}', [AdminRoomController::class, 'show']);
    Route::get('rooms/{room}/amenities', [AdminRoomController::class, 'amenities']);
    Route::get('/rooms/{room}/ratings', [RatingController::class, 'indexForRoom']);
    Route::post('hotels/{hotel}/rooms', [AdminRoomController::class, 'store']);
    Route::put('hotels/{hotel}/rooms/{room}', [AdminRoomController::class, 'update']);
    Route::delete('hotels/{hotel}/rooms/{room}', [AdminRoomController::class, 'destroy']);

    Route::post('amenities', [AdminAmenityController::class, 'store']);
    Route::put('amenities/{amenity}', [AdminAmenityController::class, 'update']);
    Route::delete('amenities/{amenity}', [AdminAmenityController::class, 'destroy']);

    Route::apiResource('tags', AdminTagController::class)->only(['store', 'update', 'destroy', 'index', 'show']);
    });
});
