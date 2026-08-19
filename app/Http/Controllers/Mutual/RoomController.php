<?php

namespace App\Http\Controllers\Mutual;

use App\Http\Controllers\Controller;
use App\Http\Resources\AmenityResource;
use App\Http\Resources\RoomResource;
use App\Models\Hotel;
use App\Models\Room;

class RoomController extends Controller
{
    public function index(Hotel $hotel)
    {
        $rooms = $hotel->rooms()->with(['description', 'amenities', 'bedTypes', 'prices.season'])->paginate(15);

        return RoomResource::collection($rooms);
    }

    public function show(Room $room)
    {
        return new RoomResource($room->load(['hotel.location', 'description', 'amenities', 'bedTypes', 'prices.season']));
    }

    /**
     * Return all amenities for the given room.
     */
    public function amenities(Room $room)
    {
        $room->load('amenities');

        return $this->succeed(__('Amenities retrieved successfully'), [
            'amenities' => AmenityResource::collection($room->amenities),
        ]);
    }
}
