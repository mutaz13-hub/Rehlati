<?php

namespace App\Http\Controllers\Mutual;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Http\Resources\CityResource;
use App\Http\Requests\City\StoreCityRequest;
use App\Http\Requests\City\UpdateCityRequest;
use App\Http\Requests\City\UpdateCityPicturesRequest;
use App\Http\Requests\City\UpdateCityThumbnailsRequest;
use App\Services\CityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CityController extends Controller
{
    public function __construct(protected CityService $cityService)
    {
    }

    public function index(): JsonResponse
    {
        $cities = City::with(['description', 'media'])->withCount('reviews')->get();
        return $this->succeed(__('Cities fetched successfully'), CityResource::collection($cities));
    }

    public function store(StoreCityRequest $request): JsonResponse
    {
        Gate::authorize('create', City::class);
        $this->cityService->createCity($request->validated());
        return $this->succeed(__('City created successfully'));
    }

    public function show(City $city): JsonResponse
    {
        $city->load(['description', 'media', 'reviews', 'topReviews.user']);
        return $this->succeed(__('City fetched successfully'), new CityResource($city));
    }

    public function update(UpdateCityRequest $request, City $city): JsonResponse
    {
        Gate::authorize('update', $city);
        $this->cityService->updateCity($city, $request->validated());
        return $this->succeed(__('City updated successfully'));
    }

    public function updatePictures(UpdateCityPicturesRequest $request, City $city): JsonResponse
    {
        Gate::authorize('update', $city);
        $this->cityService->updateCityPictures($city, $request->validated());

        return $this->succeed(__('City pictures updated successfully'));
    }

    public function updateThumbnails(UpdateCityThumbnailsRequest $request, City $city): JsonResponse
    {
        Gate::authorize('update', $city);
        $this->cityService->updateCityThumbnails($city, $request->validated());
        return $this->succeed(__('City thumbnails updated successfully'));
    }

    public function destroy(City $city): JsonResponse
    {
        Gate::authorize('delete', $city);
        $this->cityService->deleteCity($city);
        return $this->succeed(__('City deleted successfully'));
    }
}
