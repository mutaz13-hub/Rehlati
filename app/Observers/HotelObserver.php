<?php

namespace App\Observers;

use App\Models\Hotel;

class HotelObserver
{
    public function deleting(Hotel $hotel): void
    {
        $hotel->description()->delete();
        $hotel->location()->delete();
        $hotel->contactDetails()->delete();
        $hotel->reviews()->delete();
        $hotel->clearMediaCollection('hotel_pictures');
        $hotel->amenity_hotels()->delete();
        $hotel->rooms()->each(function ($room) {
            $room->delete();
        });
    }
}
