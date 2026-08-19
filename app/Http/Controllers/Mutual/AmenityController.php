<?php

namespace App\Http\Controllers\Mutual;

use App\Http\Controllers\Controller;
use App\Http\Requests\Amenity\StoreAmenityRequest;
use App\Http\Requests\Amenity\UpdateAmenityRequest;
use App\Http\Resources\AmenityResource;
use App\Models\Amenity;
use App\Services\AmenityService;
use Illuminate\Http\JsonResponse;

class AmenityController extends Controller
{
    public function index()
    {
        $amenities = Amenity::paginate(20);

        return AmenityResource::collection($amenities);
    }

    public function show(Amenity $amenity)
    {
        return new AmenityResource($amenity);
    }

    public function store(StoreAmenityRequest $request, AmenityService $amenityService): JsonResponse
    {
        $amenity = $amenityService->create($request->validated());

        return $this->succeed(__('Amenity created'), new AmenityResource($amenity));
    }

    public function update(UpdateAmenityRequest $request, Amenity $amenity, AmenityService $amenityService): JsonResponse
    {
        $amenity = $amenityService->update($amenity, $request->validated());

        return $this->succeed(__('Amenity updated'), new AmenityResource($amenity));
    }

    public function destroy(Amenity $amenity, AmenityService $amenityService): JsonResponse
    {
        $this->authorize('delete', $amenity);

        $amenityService->delete($amenity);

        return response()->json([], 204);
    }
}
