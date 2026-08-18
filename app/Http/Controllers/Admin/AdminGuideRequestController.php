<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GuideRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminGuideRequestResource;
use App\Models\GuideRequest;
use App\Services\GuideRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminGuideRequestController extends Controller
{
    public function __construct(protected GuideRequestService $guideRequestService) {}

    public function index(Request $request): JsonResponse
    {
        $requests = $this->guideRequestService->indexForAdmin($this->filters($request), 10);

        return $this->succeed(__('Guide booking requests fetched successfully'), [
            'guide_requests' => AdminGuideRequestResource::collection($requests),
            'meta' => $this->paginationMeta($requests),
        ]);
    }

    public function show(GuideRequest $guideRequest): JsonResponse
    {
        return $this->succeed(__('Guide booking request fetched successfully'), [
            'guide_request' => new AdminGuideRequestResource($this->guideRequestService->show($guideRequest)),
        ]);
    }

    public function approve(GuideRequest $guideRequest): JsonResponse
    {
        $this->guideRequestService->respond($guideRequest, GuideRequestStatus::APPROVED);

        return $this->succeed(__('Guide booking request approved successfully'));
    }

    public function reject(GuideRequest $guideRequest): JsonResponse
    {
        $this->guideRequestService->respond($guideRequest, GuideRequestStatus::REJECTED);

        return $this->succeed(__('Guide booking request rejected successfully'));
    }

    private function filters(Request $request): array
    {
        return $request->only(['status', 'q', 'tourist_guide_id', 'trip_id']);
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
