<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\City\StoreCityRequest;
use App\Http\Requests\Admin\City\UpdateCityPicturesRequest;
use App\Http\Requests\Admin\City\UpdateCityRequest;
use App\Http\Requests\Admin\City\UpdateCityThumbnailsRequest;
use App\Http\Resources\Admin\AdminCityResource;
use App\Http\Resources\Admin\AdminHotelResource;
use App\Http\Resources\Admin\AdminRegionResource;
use App\Models\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CityController extends Controller
{
    public function __construct(protected CityService $cityService) {}

    public function index(): JsonResponse
    {
        $cities = City::with(['description', 'media', 'location'])->withCount('reviews')->get();

        return $this->succeed(__('Cities fetched successfully'), AdminCityResource::collection($cities));
    }

    public function store(StoreCityRequest $request): JsonResponse
    {
        $this->cityService->createCity($request->validated());

        return $this->succeed(__('City created successfully'));
    }

    public function show(City $city): JsonResponse
    {
        $city->load('description', 'media', 'location', 'topReviews.user', 'topRegions.description', 'tags', 'top_hotels', 'topRegions.location');
        if (auth('sanctum')->check() && auth('sanctum')->user()->role('user')) {
            $city->load('myReview');
        }

        return $this->succeed(__('City fetched successfully'), [
            'city' => new AdminCityResource($city),
        ]);
    }

    public function regions(Request $request, City $city): JsonResponse
    {
        $data = $city->regions()->with(['tags', 'description', 'location'])->paginate(2);

        return $this->succeed(__('City regions fetched successfully'), [
            'regions' => AdminRegionResource::collection($data),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'total' => $data->total(),
            ],
        ]);
    }

    public function hotels(Request $request, City $city): JsonResponse
    {
        $data = $city->hotels()->with(['description', 'location', 'amenities'])->paginate(5);

        return $this->succeed(__('City hotels fetched successfully'), [
            'hotels' => AdminHotelResource::collection($data),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'total' => $data->total(),
            ],
        ]);
    }

    public function update(UpdateCityRequest $request, City $city): JsonResponse
    {
        $this->cityService->updateCity($city, $request->validated());

        return $this->succeed(__('City updated successfully'));
    }

    public function updatePictures(UpdateCityPicturesRequest $request, City $city): JsonResponse
    {
        $this->cityService->updateCityPictures($city, $request->validated());

        return $this->succeed(__('City pictures updated successfully'));
    }

    public function updateThumbnails(UpdateCityThumbnailsRequest $request, City $city): JsonResponse
    {
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
