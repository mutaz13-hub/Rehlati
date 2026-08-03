<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Hotel;
use Illuminate\Support\Facades\DB;

class RoomService
{
    public function create(Hotel $hotel, array $data): Room
    {
        return DB::transaction(function () use ($hotel, $data) {
            $data['hotel_id'] = $hotel->id;

            $room = Room::create([
                'hotel_id' => $data['hotel_id'],
                'name_en' => $data['name_en'],
                'name_ar' => $data['name_ar'],
                'room_type' => $data['room_type'],
                'bed_type' => $data['bed_type'],
                'price_per_night' => $data['price_per_night'],
                'total_rooms' => $data['total_rooms'],
                'available_rooms' => $data['available_rooms'],
            ]);

            if (isset($data['description_en']) || isset($data['description_ar'])) {
                $room->description()->create([
                    'description_en' => $data['description_en'] ?? '',
                    'description_ar' => $data['description_ar'] ?? '',
                ]);
            }

            if (isset($data['amenities']) && is_array($data['amenities'])) {
                $room->amenities()->sync($data['amenities']);
            }

            return $room;
        });
    }

    public function update(Room $room, array $data): Room
    {
        return DB::transaction(function () use ($room, $data) {
            $room->update(array_filter([
                'name_en' => $data['name_en'] ?? null,
                'name_ar' => $data['name_ar'] ?? null,
                'room_type' => $data['room_type'] ?? null,
                'bed_type' => $data['bed_type'] ?? null,
                'price_per_night' => $data['price_per_night'] ?? null,
                'total_rooms' => $data['total_rooms'] ?? null,
                'available_rooms' => $data['available_rooms'] ?? null,
            ]));

            if (isset($data['description_en']) || isset($data['description_ar'])) {
                $room->description()->updateOrCreate(
                    ['describable_id' => $room->id, 'describable_type' => Room::MORPH_KEY],
                    [
                        'description_en' => $data['description_en'] ?? $room->description->description_en ?? '',
                        'description_ar' => $data['description_ar'] ?? $room->description->description_ar ?? '',
                    ]
                );
            }

            if (isset($data['amenities']) && is_array($data['amenities'])) {
                $room->amenities()->sync($data['amenities']);
            }

            return $room;
        });
    }

    public function delete(Room $room): void
    {
        DB::transaction(function () use ($room): void {
            $room->description()->delete();
            $room->amenities()->detach();
            $room->delete();
        });
    }
}
