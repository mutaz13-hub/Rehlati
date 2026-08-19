<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Room;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookingService) {}

    public function index(Request $request): JsonResponse
    {
        $bookings = $this->bookingService->index($request->user(), $this->filters($request));

        return $this->succeed(__('Bookings fetched successfully'), [
            'bookings' => BookingResource::collection($bookings),
            'meta' => $this->paginationMeta($bookings),
        ]);
    }

    public function storeForRoom(StoreBookingRequest $request, Room $room): JsonResponse
    {
        $validated = $request->validated();
        $tempPaths = $this->storeTempFiles($validated['guests']);

        $result = $this->bookingService->storeForRoom($room, $request->user(), $validated, $tempPaths);

        return $this->succeed(__('Your reservation is being processed'), [
            'booking' => new BookingResource($result['booking']),
            'payment' => [
                'client_secret' => $result['client_secret'],
            ],
        ], 201);
    }

    public function storeForPackage(StoreBookingRequest $request, Package $package): JsonResponse
    {
        $validated = $request->validated();
        $tempPaths = $this->storeTempFiles($validated['guests']);

        $result = $this->bookingService->storeForPackage($package, $request->user(), $validated, $tempPaths);

        return $this->succeed(__('Your reservation is being processed'), [
            'booking' => new BookingResource($result['booking']),
            'payment' => [
                'client_secret' => $result['client_secret'],
            ],
        ], 201);
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        Gate::forUser($request->user())->authorize('view', $booking);

        return $this->succeed(__('Booking fetched successfully'), [
            'booking' => new BookingResource($this->bookingService->show($request->user(), $booking)),
        ]);
    }

    public function confirmPayment(Booking $booking): JsonResponse
    {
        $this->bookingService->confirmPayment($booking);

        return $this->succeed(__('Payment confirmed successfully'), [
            'booking' => new BookingResource($booking->fresh(['bookable', 'guests.media'])),
        ]);
    }

    public function destroy(Request $request, Booking $booking): JsonResponse
    {
        Gate::forUser($request->user())->authorize('delete', $booking);

        $this->bookingService->cancel($request->user(), $booking);

        return $this->succeed(__('Booking cancelled successfully'));
    }

    private function storeTempFiles(array $guests): array
    {
        $paths = [];

        foreach ($guests as $index => $guest) {
            $file = $guest['id_file'] ?? null;

            if ($file instanceof UploadedFile) {
                $paths[$index] = $file->store('temp/bookings', 'local');
            }
        }

        return $paths;
    }

    private function filters(Request $request): array
    {
        return $request->only(['status']);
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
