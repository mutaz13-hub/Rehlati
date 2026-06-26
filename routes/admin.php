<?php

use App\Http\Controllers\Mutual\HotelController;
use App\Http\Controllers\Mutual\RoomController;
use App\Http\Controllers\Mutual\RoomTypeController;
use App\Http\Controllers\Mutual\AmenityController;
use App\Http\Controllers\Mutual\CityController;
use App\Http\Controllers\Mutual\RegionController;
use App\Http\Controllers\Mutual\TagController;
use Illuminate\Support\Facades\Route;

// , 'admin' middleware is missing
Route::prefix('admin')->middleware(['check_api_password', 'check_language', 'auth:sanctum'])->group(function () {
    // Use the single mutual HotelController for admin actions as well
    Route::apiResource('hotels', HotelController::class)->only(['store', 'update', 'destroy', 'index', 'show']);

    // City management (admin)
    Route::apiResource('cities', CityController::class)->only(['store', 'update', 'destroy']);
    Route::post('cities/{city}/pictures', [CityController::class, 'updatePictures']);
    Route::post('cities/{city}/thumbnails', [CityController::class, 'updateThumbnails']);

    // Region management (admin)
    Route::apiResource('regions', RegionController::class)->only(['store', 'update', 'destroy', 'index', 'show']);
    Route::post('regions/{region}/pictures', [RegionController::class, 'updatePictures']);
    Route::post('regions/{region}/thumbnails', [RegionController::class, 'updateThumbnails']);

    // Rooms nested management (admin only) — delegated to RoomController
    Route::post('hotels/{hotel}/rooms', [RoomController::class, 'store']);
    Route::put('hotels/{hotel}/rooms/{room}', [RoomController::class, 'update']);
    Route::delete('hotels/{hotel}/rooms/{room}', [RoomController::class, 'destroy']);

    // Room types (admin)
    Route::post('room-types', [RoomTypeController::class, 'store']);
    Route::put('room-types/{roomType}', [RoomTypeController::class, 'update']);
    Route::delete('room-types/{roomType}', [RoomTypeController::class, 'destroy']);

    // Amenities (admin)
    Route::post('amenities', [AmenityController::class, 'store']);
    Route::put('amenities/{amenity}', [AmenityController::class, 'update']);
    Route::delete('amenities/{amenity}', [AmenityController::class, 'destroy']);

    // Tags (admin)
    Route::apiResource('tags', TagController::class)->only(['store', 'update', 'destroy', 'index', 'show']);
});
