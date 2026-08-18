<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guide\StoreGuideBookingRequest;
use App\Http\Resources\GuideRequestResource;
use App\Models\GuideRequest;
use App\Models\Trip;
use App\Services\GuideRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GuideRequestController extends Controller
{
    public function __construct(protected GuideRequestService $guideRequestService) {}

    public function index(Request $request, Trip $trip): JsonResponse
    {
        Gate::forUser($request->user())->authorize('view', $trip);

        return $this->succeed(__('Guide booking requests fetched successfully'), [
            'guide_requests' => GuideRequestResource::collection($this->guideRequestService->indexForTrip($trip)),
        ]);
    }

    public function store(StoreGuideBookingRequest $request, Trip $trip): JsonResponse
    {
        Gate::forUser($request->user())->authorize('bookGuide', $trip);

        $guideRequest = $this->guideRequestService->store($trip, $request->validated());

        return $this->succeed(
            __('Guide booking request sent successfully'),
            ['guide_request' => new GuideRequestResource($guideRequest->load('touristGuide'))],
            201,
        );
    }

    public function destroy(Request $request, Trip $trip, GuideRequest $guideRequest): JsonResponse
    {
        Gate::forUser($request->user())->authorize('bookGuide', $trip);

        $this->guideRequestService->cancel($trip, $guideRequest);

        return $this->succeed(__('Guide booking request cancelled successfully'));
    }
}
