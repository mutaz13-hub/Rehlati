<?php

namespace App\Http\Controllers\Mutual;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Http\Resources\HotelResource;
use App\Http\Resources\AmenityResource;
use App\Http\Requests\Hotel\StoreHotelRequest;
use App\Http\Requests\Hotel\UpdateHotelRequest;
use App\Services\HotelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

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
     * Store a newly created resource in storage.
     */
    public function store(StoreHotelRequest $request): JsonResponse
    {
        Gate::authorize('create', Hotel::class);

         $this->hotel_service->create($request->validated());

        return $this->succeed(__('Hotel is created successfully'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Hotel $hotel): JsonResponse
    {
        return $this->succeed(__('Hotel retrieved successfully'), new HotelResource(
            $hotel->load(['rooms.description', 'rooms.amenities', 'location', 'city.location', 'description', 'amenities', 'myReview', 'topReviews.user'])
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

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateHotelRequest $request, Hotel $hotel): JsonResponse
    {
        Gate::authorize('update', $hotel);

        $this->hotel_service->update($hotel, $request->validated());

        return $this->succeed(__('Hotel updated successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Hotel $hotel): JsonResponse
    {
        Gate::authorize('delete', $hotel);

        $this->hotel_service->delete($hotel);

        return $this->succeed(__('Hotel deleted successfully'));
    }

    
}
