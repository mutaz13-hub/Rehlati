<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Room;
use App\Services\DocumentUploadService;
use App\Services\PriceUserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ProcessBookingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly int $bookingId,
        private readonly array $tempFilePaths = [],
    ) {}

    public function handle(
        PriceUserService $priceService,
        DocumentUploadService $documentUploadService,
    ): void {
        $booking = Booking::with(['bookable', 'guests'])->findOrFail($this->bookingId);

        if ($booking->bookable_type === Room::MORPH_KEY) {
            $this->processRoomBooking($booking, $priceService, $documentUploadService);
        } else {
            $this->processPackageBooking($booking, $priceService, $documentUploadService);
        }
    }

    private function processRoomBooking(
        Booking $booking,
        PriceUserService $priceService,
        DocumentUploadService $documentUploadService,
    ): void {
        $room = Room::lockForUpdate()->findOrFail($booking->bookable_id);

        if ((int) $room->available_rooms < 1) {
            $this->cancelBooking($booking, 'Room is no longer available');

            return;
        }

        $nights = (int) Carbon::parse($booking->check_in)->diffInDays(Carbon::parse($booking->check_out));

        if ($nights < 1) {
            $this->cancelBooking($booking, 'Invalid date range');

            return;
        }

        $activeCurrency = $priceService->getActiveCurrency();
        $season = $priceService->resolveActiveSeason($room, $booking->check_in);

        $totalPrice = 0.0;

        foreach ($booking->guests as $guest) {
            $priceType = $guest->type === 'child' ? PriceUserService::CHILD_PRICE_TYPE : 'base_price';

            $price = $priceService->lookupMatchingPrice(
                $room,
                $priceType,
                $guest->nationality->value,
                $season?->id
            );

            if (! $price) {
                $this->cancelBooking($booking, "No {$priceType} found for {$guest->nationality->value}");

                return;
            }

            $converted = $priceService->convertToCurrency(
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

        $this->processFiles($booking, $documentUploadService);
    }

    private function processPackageBooking(
        Booking $booking,
        PriceUserService $priceService,
        DocumentUploadService $documentUploadService,
    ): void {
        $package = Package::findOrFail($booking->bookable_id);

        $activeCurrency = $priceService->getActiveCurrency();
        $season = $priceService->resolveActiveSeason($package);

        $totalPrice = 0.0;

        foreach ($booking->guests as $guest) {
            $priceType = $guest->type === 'child' ? PriceUserService::CHILD_PRICE_TYPE : 'package_price';

            $price = $priceService->lookupMatchingPrice(
                $package,
                $priceType,
                $guest->nationality->value,
                $season?->id
            );

            if (! $price) {
                $this->cancelBooking($booking, "No {$priceType} found for {$guest->nationality->value}");

                return;
            }

            $converted = $priceService->convertToCurrency(
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

        $this->processFiles($booking, $documentUploadService);
    }

    private function processFiles(Booking $booking, DocumentUploadService $documentUploadService): void
    {
        $guests = $booking->guests()->get();

        foreach ($guests as $index => $guest) {
            $tempPath = $this->tempFilePaths[$index] ?? null;

            if ($tempPath && Storage::disk('local')->exists($tempPath)) {
                $fullPath = Storage::disk('local')->path($tempPath);

                $file = new UploadedFile($fullPath, basename($tempPath));
                $documentUploadService->addUploaded($guest, $file, 'guest_id_documents');
                Storage::disk('local')->delete($tempPath);
            }
        }
    }

    private function cancelBooking(Booking $booking, string $reason): void
    {
        $booking->update(['status' => BookingStatus::CANCELLED->value]);
        $this->cleanupTempFiles();
    }

    private function cleanupTempFiles(): void
    {
        foreach ($this->tempFilePaths as $path) {
            Storage::disk('local')->delete($path);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $booking = Booking::find($this->bookingId);

        if ($booking && $booking->isPending()) {
            $booking->update(['status' => BookingStatus::CANCELLED->value]);
        }

        $this->cleanupTempFiles();
    }
}
