<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Http\Resources\Admin\AdminRegionResource;
use App\Http\Requests\Admin\Region\StoreRegionRequest;
use App\Http\Requests\Admin\Region\UpdateRegionRequest;
use App\Http\Requests\Admin\Region\UpdateRegionPicturesRequest;
use App\Http\Requests\Admin\Region\UpdateRegionThumbnailsRequest;
use App\Services\Admin\AdminRegionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AdminRegionController extends Controller
{
    public function __construct(protected RegionService $regionService)
    {
    }

    public function index(): JsonResponse
    {
        $regions = Region::with(['description', 'media', 'city.location', 'location'])->get();
        return $this->succeed(__('Regions fetched successfully'), AdminRegionResource::collection($regions));
    }

    public function store(StoreRegionRequest $request): JsonResponse
    {
        $this->regionService->createRegion($request->validated());
        return $this->succeed(__('Region created successfully'));
    }

    public function show(Region $region): JsonResponse
    {
        $region->load(['description', 'media', 'city.location', 'tags', 'location']);
       
        return $this->succeed(__('Region fetched successfully'), new AdminRegionResource($region));
    }

    public function update(UpdateRegionRequest $request, Region $region): JsonResponse
    {
        $region = $this->regionService->updateRegion($region, $request->validated());
        return $this->succeed(__('Region updated successfully'), new AdminRegionResource($region));
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
