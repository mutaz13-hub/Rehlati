<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TouristGuide\StoreTouristGuideRequest;
use App\Http\Requests\Admin\TouristGuide\UpdateTouristGuideRequest;
use App\Http\Resources\Admin\AdminTouristGuideResource;
use App\Models\TouristGuide;
use App\Services\TouristGuideService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTouristGuideController extends Controller
{
    public function __construct(protected TouristGuideService $guideService) {}

    public function index(Request $request): JsonResponse
    {
        $guides = $this->guideService->index($this->filters($request), 10);

        return $this->succeed(__('Tourist guides fetched successfully'), [
            'tourist_guides' => AdminTouristGuideResource::collection($guides),
            'meta' => $this->paginationMeta($guides),
        ]);
    }

    public function store(StoreTouristGuideRequest $request): JsonResponse
    {
        $this->guideService->create($request->validated());

        return $this->succeed(__('Tourist guide created successfully'), [], 201);
    }

    public function show(TouristGuide $touristGuide): JsonResponse
    {
        $touristGuide->loadCount('reviews')->loadAvg('reviews', 'rate');

        return $this->succeed(__('Tourist guide fetched successfully'), [
            'tourist_guide' => new AdminTouristGuideResource($touristGuide),
        ]);
    }

    public function update(UpdateTouristGuideRequest $request, TouristGuide $touristGuide): JsonResponse
    {
        $this->guideService->update($touristGuide, $request->validated());

        return $this->succeed(__('Tourist guide updated successfully'));
    }

    public function destroy(TouristGuide $touristGuide): JsonResponse
    {
        $this->guideService->delete($touristGuide);

        return $this->succeed(__('Tourist guide deleted successfully'));
    }

    private function filters(Request $request): array
    {
        $filters = $request->only(['q', 'is_active']);

        if (array_key_exists('is_active', $filters)) {
            $filters['is_active'] = $request->boolean('is_active');
        }

        return $filters;
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
