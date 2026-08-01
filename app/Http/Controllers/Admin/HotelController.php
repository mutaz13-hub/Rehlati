<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Http\Resources\Admin\AdminHotelResource;
use App\Http\Resources\Admin\AdminAmenityResource;
use App\Http\Requests\Admin\Hotel\StoreHotelRequest;
use App\Http\Requests\Admin\Hotel\UpdateHotelRequest;
use App\Services\Admin\AdminHotelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class HotelController extends Controller
{
    public function __construct(public HotelService $hotel_service)
    {
    }

    public function index(): JsonResponse
    {
        $hotels = Hotel::with(['rooms.description', 'rooms.amenities', 'location', 'city.location', 'description', 'amenities'])
            ->withCount(['rooms', 'reviews'])
            ->withAvg('reviews', 'rate')
            ->paginate(10);

        return $this->succeed(__('Hotels fetched successfully'), AdminHotelResource::collection($hotels));
    }

    public function store(StoreHotelRequest $request): JsonResponse
    {
        Gate::authorize('create', Hotel::class);

        $this->hotel_service->create($request->validated());

        return $this->succeed(__('Hotel is created successfully'));
    }

    public function show(Hotel $hotel): JsonResponse
    {
        return $this->succeed(__('Hotel retrieved successfully'), new AdminHotelResource(
            $hotel->load(['rooms.description', 'rooms.amenities', 'location', 'city.location', 'description', 'amenities', 'myReview', 'topReviews.user'])
                ->loadCount(['rooms', 'reviews'])
                ->loadAvg('reviews', 'rate')
        ));
    }

    public function amenities(Hotel $hotel): JsonResponse
    {
        $hotel->load('amenities');

        return $this->succeed(__('Amenities retrieved successfully'), [
            'amenities' => AdminAmenityResource::collection($hotel->amenities)
        ]);
    }

    public function update(UpdateHotelRequest $request, Hotel $hotel): JsonResponse
    {
        Gate::authorize('update', $hotel);

        $this->hotel_service->update($hotel, $request->validated());

        return $this->succeed(__('Hotel updated successfully'));
    }

    public function destroy(Hotel $hotel): JsonResponse
    {
        Gate::authorize('delete', $hotel);

        $this->hotel_service->delete($hotel);

        return $this->succeed(__('Hotel deleted successfully'));
    }
}
