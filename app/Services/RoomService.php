<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Hotel;

class RoomService
{
    public function create(Hotel $hotel, array $data): Room
    {
        $data['hotel_id'] = $hotel->id;

        return Room::create($data);
    }

    public function update(Room $room, array $data): Room
    {
        $room->update($data);

        return $room;
    }

    public function delete(Room $room): void
    {
        $room->delete();
    }
}
