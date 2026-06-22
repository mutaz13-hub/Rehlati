<?php

namespace App\Http\Controllers\Mutual;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Http\Resources\HotelResource;
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
        $hotels = Hotel::with(['rooms', 'location', 'city'])->paginate(15);

        return HotelResource::collection($hotels);
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
        return $this->succeed(__('Hotel retrieved successfully'), new HotelResource($hotel->load(['rooms', 'location'])));
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
