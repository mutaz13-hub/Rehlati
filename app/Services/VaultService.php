<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class VaultService
{
    /**
     * Return every document the user submitted through their bookings,
     * flattened into a single "vault" list for the Flutter app.
     */
    public function documents(User $user): Collection
    {
        return $user->bookings()
            ->with(['guests.media'])
            ->latest()
            ->get()
            ->flatMap(function (Booking $booking) {
                return $booking->guests->flatMap(function (BookingGuest $guest) use ($booking) {
                    return $guest->getMedia('guest_id_documents')->map(function (Media $media) use ($booking, $guest) {
                        return [
                            'id' => $media->id,
                            'name' => $media->name,
                            'file_name' => $media->file_name,
                            'mime_type' => $media->mime_type,
                            'size' => $media->size,
                            'url' => $media->getUrl(),
                            'uploaded_at' => $media->created_at?->toDateTimeString(),
                            'booking_reference' => $booking->booking_reference,
                            'booking_status' => $booking->status?->value,
                            'guest_name' => $guest->full_name,
                            'nationality' => $guest->nationality?->value,
                            'bookable_type' => $booking->bookable_type,
                            'bookable_id' => $booking->bookable_id,
                        ];
                    });
                });
            })
            ->values();
    }
}
