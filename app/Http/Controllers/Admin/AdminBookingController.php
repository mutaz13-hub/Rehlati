<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminBookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBookingController extends Controller
{
    public function __construct(private readonly BookingService $bookingService) {}

    public function index(Request $request): JsonResponse
    {
        $bookings = $this->bookingService->indexForAdmin($this->filters($request), 10);

        return $this->succeed(__('Bookings fetched successfully'), [
            'bookings' => AdminBookingResource::collection($bookings),
            'meta' => $this->paginationMeta($bookings),
        ]);
    }

    public function show(Booking $booking): JsonResponse
    {
        return $this->succeed(__('Booking fetched successfully'), [
            'booking' => new AdminBookingResource($this->bookingService->showForAdmin($booking)),
        ]);
    }

    public function approve(Booking $booking): JsonResponse
    {
        $this->bookingService->approve($booking);

        return $this->succeed(__('Booking approved successfully'));
    }

    public function reject(Booking $booking): JsonResponse
    {
        $this->bookingService->reject($booking);

        return $this->succeed(__('Booking rejected successfully'));
    }

    private function filters(Request $request): array
    {
        return $request->only(['status', 'q']);
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
