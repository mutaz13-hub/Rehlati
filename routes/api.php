<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\CommunityController;
use App\Http\Controllers\Api\CommunityMessageController;
use App\Http\Controllers\Api\EmergencyNumberController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\GuideRequestController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\TripTrackingController;
use App\Http\Controllers\Api\VaultController;
use App\Http\Controllers\Mutual\AmenityController;
use App\Http\Controllers\Mutual\CityController;
use App\Http\Controllers\Mutual\HotelController;
use App\Http\Controllers\Mutual\PackageController;
use App\Http\Controllers\Mutual\PictureController;
use App\Http\Controllers\Mutual\RegionController;
use App\Http\Controllers\Mutual\RoomController;
use App\Http\Controllers\Mutual\TagController;
use App\Http\Controllers\Mutual\TouristGuideController;
use App\Models\City;
use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Support\Facades\Route;

Route::middleware(['check_api_password', 'check_language'])->group(function () {

    require __DIR__.'/admin.php';
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware(['ensure_can_refresh', 'throttle:refresh']);

    // Public deep-linking: anyone holding the uuid may view the shared trip.

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

            // Public package (planned trip) listings and details
            Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
            Route::get('/packages/{package}', [PackageController::class, 'show'])->name('packages.show');

            // Ratings
            Route::post('/ratings/cities/{id}', [RatingController::class, 'store'])->name('ratings.cities');
            Route::post('/ratings/hotels/{id}', [RatingController::class, 'store'])->name('ratings.hotels');
            Route::post('/ratings/rooms/{id}', [RatingController::class, 'store'])->name('ratings.rooms');
            Route::post('/ratings/regions/{id}', [RatingController::class, 'store'])->name('ratings.regions');
            Route::post('/ratings/car_agencies/{id}', [RatingController::class, 'store'])->name('ratings.car_agencies');
            Route::post('/ratings/tourist_guides/{id}', [RatingController::class, 'store'])->name('ratings.tourist_guides');

            Route::patch('/ratings/{rating}', [RatingController::class, 'update']);
            Route::delete('/ratings/{rating}', [RatingController::class, 'destroy']);
            Route::post('/ratings/{rating}/vote', [RatingController::class, 'vote']);

            Route::get('/ratings/{rating}', [RatingController::class, 'show']);
            // Public amenities and tags
            Route::get('/amenities', [AmenityController::class, 'index']);
            Route::get('/amenities/{amenity}', [AmenityController::class, 'show']);
            // Route::get('/tags', [TagController::class, 'index']);
            // Route::get('/tags/{tag}', [TagController::class, 'show']);

            // Tourist guides (user-facing listing and ratings)
            Route::get('/tourist-guides', [TouristGuideController::class, 'index'])->name('tourist-guides.index');
            Route::get('/tourist-guides/{touristGuide}', [TouristGuideController::class, 'show'])->name('tourist-guides.show');
            Route::get('/tourist-guides/{touristGuide}/ratings', [RatingController::class, 'indexForGuide'])->name('tourist-guides.ratings');

            // Trip planning, live tracking and route archiving
            Route::apiResource('trips', TripTrackingController::class)->only(['index', 'store', 'update', 'show', 'destroy']);
            Route::post('/trips/{trip}/cities', [TripTrackingController::class, 'storePlannedCity']);
            Route::put('/trips/{trip}/cities', [TripTrackingController::class, 'updatePlannedCity']);
            Route::delete('/trips/{trip}/cities/{tripCity}', [TripTrackingController::class, 'removePlannedCity']);
            Route::post('/trips/{trip}/destinations', [TripTrackingController::class, 'storePlannedDestination']);
            Route::put('/trips/{trip}/destinations', [TripTrackingController::class, 'updatePlannedDestination']);
            Route::delete('/trips/{trip}/destinations/{tripDestination}', [TripTrackingController::class, 'removePlannedDestination']);
            Route::patch('/trips/{trip}/status', [TripTrackingController::class, 'updateStatus']);
            Route::post('/trips/{trip}/pings', [TripTrackingController::class, 'storeLocationPing']);
            Route::post('/trips/{trip}/notes', [TripTrackingController::class, 'storeTripNote']);
            Route::post('/trips/{trip}/members', [TripTrackingController::class, 'inviteMember']);
            Route::put('/trips/{trip}/members/{user}', [TripTrackingController::class, 'updateMemberRole']);
            Route::delete('/trips/{trip}/members/{tripMember}', [TripTrackingController::class, 'removeMember']);
            Route::post('/trips/{trip}/members/{user}/accept', [TripTrackingController::class, 'acceptInvitation']);
            Route::post('/trips/{trip}/members/{user}/reject', [TripTrackingController::class, 'rejectInvitation']);
            Route::post('/trips/{trip}/rotate-link', [TripTrackingController::class, 'rotateLink']);
            Route::get('trips/shared-trips/{uuid}', [TripTrackingController::class, 'showByUuid'])->name('shared-trips.show');

            // Guide booking requests on a custom trip
            Route::get('/trips/{trip}/guides', [GuideRequestController::class, 'index'])->name('trips.guide-requests.index');
            Route::post('/trips/{trip}/guides', [GuideRequestController::class, 'store'])->name('trips.guide-requests.store');
            Route::delete('/trips/{trip}/guides/{guideRequest}', [GuideRequestController::class, 'destroy'])->name('trips.guide-requests.destroy');

            // Communities
            Route::apiResource('communities', CommunityController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
            Route::get('/communities/{community}/members', [CommunityController::class, 'members']);
            Route::post('/communities/{community}/join', [CommunityController::class, 'join']);
            Route::post('/communities/{community}/leave', [CommunityController::class, 'leave']);
            Route::put('/communities/{community}/members/{communityMember}', [CommunityController::class, 'updateMemberRole']);
            Route::post('/communities/{community}/members/{communityMember}/approve', [CommunityController::class, 'approveMember']);
            Route::post('/communities/{community}/members/{communityMember}/reject', [CommunityController::class, 'rejectMember']);
            Route::delete('/communities/{community}/members/{communityMember}', [CommunityController::class, 'removeMember']);
            Route::post('/communities/{community}/rotate-link', [CommunityController::class, 'rotateLink']);

            // Posts
            Route::get('/communities/{community}/posts', [PostController::class, 'index']);
            Route::post('/communities/{community}/posts', [PostController::class, 'store']);
            Route::get('/posts/{post}', [PostController::class, 'show']);
            Route::patch('/posts/{post}', [PostController::class, 'update']);
            Route::delete('/posts/{post}', [PostController::class, 'destroy']);
            Route::post('/posts/{post}/vote', [PostController::class, 'vote']);

            // Comments
            Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
            Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
            Route::patch('/comments/{comment}', [CommentController::class, 'update']);
            Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
            Route::post('/comments/{comment}/vote', [CommentController::class, 'vote']);

            // Bookings for rooms and packages (payment handled separately later)
            Route::post('/rooms/{room}/bookings', [BookingController::class, 'storeForRoom'])->name('bookings.rooms.store');
            Route::post('/packages/{package}/bookings', [BookingController::class, 'storeForPackage'])->name('bookings.packages.store');
            Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
            Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
            Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');
            Route::post('/bookings/{booking}/confirm-payment', [BookingController::class, 'confirmPayment'])->name('bookings.confirm-payment');

            // Community chat (no realtime yet)
            Route::get('/communities/{community}/messages', [CommunityMessageController::class, 'index'])->name('communities.messages.index');
            Route::post('/communities/{community}/messages', [CommunityMessageController::class, 'store'])->name('communities.messages.store');
            Route::delete('/communities/{community}/messages/{message}', [CommunityMessageController::class, 'destroy'])->name('communities.messages.destroy');

            // Vault: documents the user submitted with their bookings
            Route::get('/vault', [VaultController::class, 'index'])->name('vault.index');

            // Emergency numbers
            Route::get('/emergency-numbers', [EmergencyNumberController::class, 'index'])->name('emergency-numbers.index');

        });
    });

});
