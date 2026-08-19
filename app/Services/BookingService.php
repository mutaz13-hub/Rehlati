<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\Package;
use App\Models\Room;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(
        private readonly DocumentUploadService $documentUploadService,
        private readonly PaymentService $paymentService,
        private readonly PriceUserService $priceUserService,
    ) {}

    public function index(User $user, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return Booking::query()
            ->where('user_id', $user->id)
            ->with(['bookable', 'guests.media'])
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->latest()
            ->paginate($perPage);
    }

    public function show(User $user, Booking $booking): Booking
    {
        $this->assertOwnedBy($booking, $user);

        return $booking->load(['bookable', 'guests.media']);
    }

    public function storeForRoom(Room $room, User $user, array $data, array $tempPaths = []): array
    {
        $this->assertRoomDates($data);
        $this->assertRoomAvailability($room);

        $booking = DB::transaction(function () use ($room, $user, $data) {
            $booking = $room->bookings()->create([
                'user_id' => $user->id,
                'status' => BookingStatus::PENDING->value,
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'guests_count' => count($data['guests']),
                'note' => $data['note'] ?? null,
            ]);

            $this->createGuests($booking, $data['guests']);

            return $booking;
        });

        $this->calculateRoomPrice($booking, $room);
        $this->processFiles($booking, $tempPaths);

        $paymentResult = $this->createPaymentIntent($booking);

        return [
            'booking' => $booking->load(['bookable', 'guests.media']),
            'client_secret' => $paymentResult['client_secret'],
        ];
    }

    public function storeForPackage(Package $package, User $user, array $data, array $tempPaths = []): array
    {
        $booking = DB::transaction(function () use ($package, $user, $data) {
            $booking = $package->bookings()->create([
                'user_id' => $user->id,
                'status' => BookingStatus::PENDING->value,
                'guests_count' => count($data['guests']),
                'note' => $data['note'] ?? null,
            ]);

            $this->createGuests($booking, $data['guests']);

            return $booking;
        });

        $this->calculatePackagePrice($booking, $package);
        $this->processFiles($booking, $tempPaths);

        $paymentResult = $this->createPaymentIntent($booking);

        return [
            'booking' => $booking->load(['bookable', 'guests.media']),
            'client_secret' => $paymentResult['client_secret'],
        ];
    }

    public function confirmPayment(Booking $booking): void
    {
        if (! $booking->payment_intent_id) {
            throw ValidationException::withMessages([
                'booking' => __('No payment intent found for this booking'),
            ]);
        }

        if ($booking->payment_status?->value === PaymentStatus::SUCCEEDED->value) {
            return;
        }

        $paymentResult = $this->paymentService->retrievePaymentIntent($booking->payment_intent_id);

        if ($paymentResult['status'] === 'succeeded') {
            $booking->update(['payment_status' => PaymentStatus::SUCCEEDED->value]);
        } else {
            throw ValidationException::withMessages([
                'payment' => __('Payment has not been completed yet'),
            ]);
        }
    }

    public function cancel(User $user, Booking $booking): void
    {
        $this->assertOwnedBy($booking, $user);

        if (! $booking->isPending()) {
            throw ValidationException::withMessages([
                'booking' => __('Only pending bookings can be cancelled'),
            ]);
        }

        DB::transaction(function () use ($booking) {
            $this->restoreRoomAvailability($booking);
            $booking->update(['status' => BookingStatus::CANCELLED->value]);
        });
    }

    public function indexForAdmin(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return Booking::query()
            ->with(['user:id,name,email', 'bookable', 'guests.media'])
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['q']), fn ($query) => $query->whereHas('user', function ($userQuery) use ($filters) {
                $userQuery->where('name', 'like', "%{$filters['q']}%")
                    ->orWhere('email', 'like', "%{$filters['q']}%")
                    ->orWhere('phone_number', 'like', "%{$filters['q']}%");
            }))
            ->latest()
            ->paginate($perPage);
    }

    public function showForAdmin(Booking $booking): Booking
    {
        return $booking->load(['user:id,name,email,phone_number', 'bookable', 'guests.media']);
    }

    public function approve(Booking $booking): void
    {
        if (! $booking->isPending()) {
            throw ValidationException::withMessages([
                'booking' => __('Only pending bookings can be approved'),
            ]);
        }

        $booking->update(['status' => BookingStatus::CONFIRMED->value]);
    }

    public function reject(Booking $booking): void
    {
        if (! $booking->isPending() && ! $booking->isConfirmed()) {
            throw ValidationException::withMessages([
                'booking' => __('This booking has already been handled'),
            ]);
        }

        DB::transaction(function () use ($booking) {
            $this->restoreRoomAvailability($booking);
            $booking->update(['status' => BookingStatus::CANCELLED->value]);
        });
    }

    private function calculateRoomPrice(Booking $booking, Room $room): void
    {
        if ((int) $room->available_rooms < 1) {
            $this->cancelBookingSilently($booking, 'Room is no longer available');

            return;
        }

        $nights = (int) Carbon::parse($booking->check_in)->diffInDays(Carbon::parse($booking->check_out));

        if ($nights < 1) {
            $this->cancelBookingSilently($booking, 'Invalid date range');

            return;
        }

        $activeCurrency = $this->priceUserService->getActiveCurrency();
        $season = $this->priceUserService->resolveActiveSeason($room, $booking->check_in);

        $totalPrice = 0.0;

        foreach ($booking->guests as $guest) {
            $priceType = $guest->type === 'child' ? PriceUserService::CHILD_PRICE_TYPE : 'base_price';

            $price = $this->priceUserService->lookupMatchingPrice(
                $room,
                $priceType,
                $guest->nationality->value,
                $season?->id
            );

            if (! $price) {
                $this->cancelBookingSilently($booking, "No {$priceType} found for {$guest->nationality->value}");

                return;
            }

            $converted = $this->priceUserService->convertToCurrency(
                (float) $price->amount,
                $price->currency,
                $activeCurrency
            );

            $totalPrice += $converted * $nights;
        }

        $room->decrement('available_rooms');

        $booking->update([
            'total_price' => round($totalPrice, 2),
            'currency' => $activeCurrency,
        ]);
    }

    private function calculatePackagePrice(Booking $booking, Package $package): void
    {
        $activeCurrency = $this->priceUserService->getActiveCurrency();
        $season = $this->priceUserService->resolveActiveSeason($package);

        $totalPrice = 0.0;

        foreach ($booking->guests as $guest) {
            $priceType = $guest->type === 'child' ? PriceUserService::CHILD_PRICE_TYPE : 'package_price';

            $price = $this->priceUserService->lookupMatchingPrice(
                $package,
                $priceType,
                $guest->nationality->value,
                $season?->id
            );

            if (! $price) {
                $this->cancelBookingSilently($booking, "No {$priceType} found for {$guest->nationality->value}");

                return;
            }

            $converted = $this->priceUserService->convertToCurrency(
                (float) $price->amount,
                $price->currency,
                $activeCurrency
            );

            $totalPrice += $converted;
        }

        $booking->update([
            'total_price' => round($totalPrice, 2),
            'currency' => $activeCurrency,
        ]);
    }

    private function createPaymentIntent(Booking $booking): array
    {
        $booking->update(['payment_method' => 'stripe']);

        try {
            $result = $this->paymentService->createPaymentIntent(
                (float) $booking->total_price,
                $booking->currency,
                ['booking_id' => $booking->id, 'booking_reference' => $booking->booking_reference]
            );

            $booking->update([
                'payment_intent_id' => $result['id'],
                'payment_status' => PaymentStatus::PENDING->value,
            ]);

            return $result;
        } catch (\Throwable $e) {
            Log::error('Payment intent creation failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            $booking->update(['payment_status' => PaymentStatus::FAILED->value]);

            throw ValidationException::withMessages([
                'payment' => __('Failed to initialize payment. Please try again'),
            ]);
        }
    }

    private function processFiles(Booking $booking, array $tempPaths): void
    {
        $guests = $booking->guests()->get();

        foreach ($guests as $index => $guest) {
            $tempPath = $tempPaths[$index] ?? null;

            if ($tempPath && Storage::disk('local')->exists($tempPath)) {
                $fullPath = Storage::disk('local')->path($tempPath);
                $file = new UploadedFile($fullPath, basename($tempPath));
                $this->documentUploadService->addUploaded($guest, $file, 'guest_id_documents');
                Storage::disk('local')->delete($tempPath);
            }
        }
    }

    private function cancelBookingSilently(Booking $booking, string $reason): void
    {
        Log::warning('Booking cancelled during processing', [
            'booking_id' => $booking->id,
            'reason' => $reason,
        ]);

        $booking->update([
            'status' => BookingStatus::CANCELLED->value,
            'payment_status' => PaymentStatus::FAILED->value,
        ]);
    }

    private function createGuests(Booking $booking, array $guests): void
    {
        foreach ($guests as $guest) {
            $bookingGuest = $booking->guests()->create([
                'full_name' => $guest['full_name'],
                'nationality' => $guest['nationality'],
                'type' => $guest['type'],
                'national_id' => $guest['national_id'] ?? null,
            ]);

            $this->attachGuestDocuments($bookingGuest, $guest);
        }
    }

    private function attachGuestDocuments(BookingGuest $bookingGuest, array $guest): void
    {
        foreach (['id_file', 'document'] as $key) {
            $file = $guest[$key] ?? null;

            if ($file instanceof UploadedFile) {
                $this->documentUploadService->addUploaded($bookingGuest, $file, 'guest_id_documents');
            }
        }
    }

    private function restoreRoomAvailability(Booking $booking): void
    {
        if ($booking->bookable_type !== Room::MORPH_KEY) {
            return;
        }

        $room = Room::whereKey($booking->bookable_id)->first();

        if ($room && $room->available_rooms < $room->total_rooms) {
            $room->increment('available_rooms');
        }
    }

    private function assertOwnedBy(Booking $booking, User $user): void
    {
        if ($booking->user_id !== $user->id) {
            abort(404);
        }
    }

    private function assertRoomDates(array $data): void
    {
        if (empty($data['check_in']) || empty($data['check_out'])) {
            throw ValidationException::withMessages([
                'check_in' => __('Check-in and check-out dates are required when booking a room'),
            ]);
        }

        if ($data['check_in'] > $data['check_out']) {
            throw ValidationException::withMessages([
                'check_out' => __('The check-out date must be after the check-in date'),
            ]);
        }
    }

    private function assertRoomAvailability(Room $room): void
    {
        if ((int) $room->available_rooms < 1) {
            throw ValidationException::withMessages([
                'room' => __('This room is currently not available'),
            ]);
        }
    }
}
