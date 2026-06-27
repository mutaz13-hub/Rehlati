<?php

namespace App\Http\Controllers\Mutual;

use App\Http\Controllers\Controller;
use App\Http\Requests\City\CityRegionsRequest;
use App\Models\City;
use App\Http\Resources\CityResource;
use App\Http\Requests\City\StoreCityRequest;
use App\Http\Requests\City\UpdateCityRequest;
use App\Http\Requests\City\UpdateCityPicturesRequest;
use App\Http\Requests\City\UpdateCityThumbnailsRequest;
use App\Http\Requests\City\ShowCityRequest;
use App\Http\Resources\RegionResource;
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

    public function show(ShowCityRequest $request, City $city): JsonResponse
    {
        $city->load('description', 'media', 'topReviews.user', 'topRegions.description', 'tags');
        if (auth('sanctum')->user()->role('user')) {
            $city->load('myReview');
        }
        
        return $this->succeed(__('City fetched successfully'), [
            'city' => new CityResource($city)
        ]);
    }

    public function regions(CityRegionsRequest $request, City $city): JsonResponse
    {
        $data = $city->regions()->with(['tags', 'description'])->paginate(2);

        return $this->succeed(__('City regions fetched successfully'), [
            'regions' => RegionResource::collection($data),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'total' => $data->total()
            ] 
        ]);
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
