<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Amenity\StoreAmenityRequest;
use App\Http\Requests\Admin\Amenity\UpdateAmenityRequest;
use App\Http\Resources\Admin\AdminAmenityResource;
use App\Models\Amenity;
use App\Services\Admin\AdminAmenityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AmenityController extends Controller
{
    public function __construct(public AdminAmenityService $amenity_service) {}

    public function index()
    {
        $amenities = Amenity::paginate(20);

        return AdminAmenityResource::collection($amenities);
    }

    public function show(Amenity $amenity)
    {
        return new AdminAmenityResource($amenity);
    }

    public function store(StoreAmenityRequest $request): JsonResponse
    {
        $amenity = $this->amenity_service->create($request->validated());

        return $this->succeed(__('Amenity created'), new AdminAmenityResource($amenity));
    }

    public function update(UpdateAmenityRequest $request, Amenity $amenity): JsonResponse
    {
        $amenity = $this->amenity_service->update($amenity, $request->validated());

        return $this->succeed(__('Amenity updated'), new AdminAmenityResource($amenity));
    }

    public function destroy(Amenity $amenity): JsonResponse
    {
        Gate::authorize('delete', $amenity);

        $this->amenity_service->delete($amenity);

        return response()->json([], 204);
    }
}
