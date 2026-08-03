<?php

namespace App\Observers;

use App\Models\Room;

class RoomObserver
{
    public function deleting(Room $room): void
    {
        $room->description()->delete();
        $room->amenities()->detach();
        $room->clearMediaCollection('room_pictures');
    }
}
