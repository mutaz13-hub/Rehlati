<?php

namespace App\Http\Controllers\Mutual;

use App\Http\Controllers\Controller;
use App\Http\Resources\TouristGuideResource;
use App\Models\TouristGuide;
use App\Services\TouristGuideService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TouristGuideController extends Controller
{
    public function __construct(protected TouristGuideService $guideService) {}

    public function index(Request $request): JsonResponse
    {
        $guides = $this->guideService->listActive($this->filters($request));

        return $this->succeed(__('Tourist guides fetched successfully'), [
            'tourist_guides' => TouristGuideResource::collection($guides),
        ]);
    }

    public function show(TouristGuide $touristGuide): JsonResponse
    {
        abort_unless($touristGuide->is_active, 404);

        $touristGuide->loadCount('reviews')->loadAvg('reviews', 'rate');

        return $this->succeed(__('Tourist guide fetched successfully'), [
            'tourist_guide' => new TouristGuideResource($touristGuide),
        ]);
    }

    private function filters(Request $request): array
    {
        return $request->only(['q']);
    }
}
