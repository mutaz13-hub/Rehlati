<?php

namespace App\Http\Controllers\Mutual;

use App\Http\Controllers\Controller;
use App\Http\Requests\City\CityRegionsRequest;
use App\Models\Region;
use App\Http\Resources\RegionResource;
use App\Http\Requests\Region\StoreRegionRequest;
use App\Http\Requests\Region\UpdateRegionRequest;
use App\Http\Requests\Region\UpdateRegionPicturesRequest;
use App\Http\Requests\Region\UpdateRegionThumbnailsRequest;
use App\Models\City;
use App\Services\RegionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class RegionController extends Controller
{
    public function __construct(protected RegionService $regionService)
    {
    }

    public function index(): JsonResponse
    {
        $regions = Region::with(['description', 'media', 'city'])->get();
        return $this->succeed(__('Regions fetched successfully'), RegionResource::collection($regions));
    }

    public function store(StoreRegionRequest $request): JsonResponse
    {
        $this->regionService->createRegion($request->validated());
        return $this->succeed(__('Region created successfully'));
    }

    public function show(Region $region): JsonResponse
    {
        $region->load(['description', 'media', 'city', 'tags']);
       
        return $this->succeed(__('Region fetched successfully'), new RegionResource($region));
    }

    public function update(UpdateRegionRequest $request, Region $region): JsonResponse
    {
        $region = $this->regionService->updateRegion($region, $request->validated());
        return $this->succeed(__('Region updated successfully'), new RegionResource($region));
    }

    public function updatePictures(UpdateRegionPicturesRequest $request, Region $region): JsonResponse
    {
        Gate::authorize('update', $region);
        $this->regionService->updateRegionPictures($region, $request->validated());
        return $this->succeed(__('Region pictures updated successfully'));
    }

    public function updateThumbnails(UpdateRegionThumbnailsRequest $request, Region $region): JsonResponse
    {
        Gate::authorize('update', $region);
        $this->regionService->updateRegionThumbnails($region, $request->validated());
        return $this->succeed(__('Region thumbnails updated successfully'));
    }

    public function destroy(Region $region): JsonResponse
    {
        $this->regionService->deleteRegion($region);
        return $this->succeed(__('Region deleted successfully'));
    }
}

