<?php

namespace App\Http\Controllers\Mutual;

use App\Http\Controllers\Controller;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use App\Services\PackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function __construct(protected PackageService $packageService) {}

    public function index(Request $request): JsonResponse
    {
        $packages = $this->packageService->index($this->filters($request), 10, onlyActive: true);

        return $this->succeed(__('Packages fetched successfully'), [
            'packages' => PackageResource::collection($packages),
            'meta' => $this->paginationMeta($packages),
        ]);
    }

    public function show(Package $package): JsonResponse
    {
        $package = $this->packageService->showActive($package);

        return $this->succeed(__('Package fetched successfully'), [
            'package' => new PackageResource($package),
        ]);
    }

    private function filters(Request $request): array
    {
        return $request->only([
            'q',
            'start_date_from',
            'start_date_to',
            'end_date_from',
            'end_date_to',
            'region_id',
            'city_id',
            'hotel_id',
            'car_agency_id',
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
