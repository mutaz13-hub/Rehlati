<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Package\UpdatePackagePicturesRequest;
use App\Http\Requests\Admin\Package\UpdatePackageThumbnailsRequest;
use App\Http\Requests\Package\StorePackageRequest;
use App\Http\Requests\Package\UpdatePackageRequest;
use App\Http\Resources\Admin\AdminPackageResource;
use App\Models\Package;
use App\Services\PackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPackageController extends Controller
{
    public function __construct(protected PackageService $packageService) {}

    public function index(Request $request): JsonResponse
    {
        $packages = $this->packageService->index($this->filters($request), 5);

        return $this->succeed(__('Packages fetched successfully'), [
            'packages' => AdminPackageResource::collection($packages),
            'meta' => $this->paginationMeta($packages),
        ]);
    }

    public function store(StorePackageRequest $request): JsonResponse
    {
        $this->packageService->createPackage($request->validated());

        return $this->succeed(__('Package created successfully'), [], 201);
    }

    public function show(Package $package): JsonResponse
    {
        $package->load(['description', 'regions', 'cities', 'hotels', 'carAgencies', 'touristGuides', 'prices']);

        return $this->succeed(__('Package fetched successfully'), ['package' => new AdminPackageResource($package)]);
    }

    public function update(UpdatePackageRequest $request, Package $package): JsonResponse
    {
        $this->packageService->updatePackage($package, $request->validated());

        return $this->succeed(__('Package updated successfully'));
    }

    public function updatePictures(UpdatePackagePicturesRequest $request, Package $package): JsonResponse
    {
        $this->packageService->updatePackagePictures($package, $request->validated());

        return $this->succeed(__('Package pictures updated successfully'));
    }

    public function updateThumbnails(UpdatePackageThumbnailsRequest $request, Package $package): JsonResponse
    {
        $this->packageService->updatePackageThumbnails($package, $request->validated());

        return $this->succeed(__('Package thumbnails updated successfully'));
    }

    public function destroy(Package $package): JsonResponse
    {
        $this->packageService->deletePackage($package);

        return $this->succeed(__('Package deleted successfully'));
    }

    private function filters(Request $request): array
    {
        return $request->only([
            'q',
            'status',
            'start_date_from',
            'start_date_to',
            'end_date_from',
            'end_date_to',
            'region_id',
            'city_id',
            'hotel_id',
            'car_agency_id',
            'tourist_guide_id',
        ]);
    }

    private function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
