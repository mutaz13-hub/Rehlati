<?php

namespace App\Http\Controllers\Mutual;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Http\Resources\HotelResource;
use App\Http\Resources\AmenityResource;
use App\Services\HotelService;
use Illuminate\Http\JsonResponse;

class HotelController extends Controller
{
    public function __construct(public HotelService $hotel_service)
    {
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $hotels = Hotel::with(['rooms.description', 'rooms.amenities', 'location', 'city.location', 'description', 'amenities'])
            ->withCount(['rooms', 'reviews'])
            ->withAvg('reviews', 'rate')
            ->paginate(10);

        return $this->succeed(__('Hotels fetched successfully'), HotelResource::collection($hotels));
    }

    /**
     * Display the specified resource.
     */
    public function show(Hotel $hotel): JsonResponse
    {
        return $this->succeed(__('Hotel retrieved successfully'), new HotelResource(
            $hotel->load(['rooms.description', 'rooms.bedTypes', 'rooms.amenities', 'location', 'city.location', 'description', 'amenities', 'myReview', 'topReviews.user'])
                ->loadCount(['rooms', 'reviews'])
                ->loadAvg('reviews', 'rate')
        ));
    }

    /**
     * Return all amenities for the given hotel.
     */
    public function amenities(Hotel $hotel): JsonResponse
    {
        $hotel->load('amenities');

        return $this->succeed(__('Amenities retrieved successfully'), [
            'amenities' => AmenityResource::collection($hotel->amenities)
        ]);
    }
}
