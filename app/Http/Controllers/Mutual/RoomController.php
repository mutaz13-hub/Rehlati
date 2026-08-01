<?php

namespace App\Http\Controllers\Mutual;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoomResource;
use App\Http\Resources\AmenityResource;
use App\Http\Requests\Room\StoreRoomRequest;
use App\Http\Requests\Room\UpdateRoomRequest;
use App\Models\Hotel;
use App\Models\Room;
use App\Services\RoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class RoomController extends Controller
{
    public function index(Hotel $hotel)
    {
        $rooms = $hotel->rooms()->with(['description', 'amenities'])->paginate(15);

        return RoomResource::collection($rooms);
    }

    public function show(Room $room)
    {
        return new RoomResource($room->load(['hotel.location', 'description', 'amenities']));
    }

    /**
     * Return all amenities for the given room.
     */
    public function amenities(Room $room)
    {
        $room->load('amenities');

        return $this->succeed(__('Amenities retrieved successfully'), [
            'amenities' => AmenityResource::collection($room->amenities)
        ]);
    }

    public function store(StoreRoomRequest $request, Hotel $hotel, RoomService $roomService): JsonResponse
    {
        // authorization checked in StoreRoomRequest->authorize()
        $room = $roomService->create($hotel, $request->validated());

        return $this->succeed(__('Room created'), new RoomResource($room));
    }

    public function update(UpdateRoomRequest $request, Hotel $hotel, Room $room, RoomService $roomService): JsonResponse
    {
        // authorization checked in UpdateRoomRequest->authorize()
        $room = $roomService->update($room, $request->validated());

        return $this->succeed(__('Room updated'), new RoomResource($room));
    }

    public function destroy(Hotel $hotel, Room $room, RoomService $roomService): JsonResponse
    {
        // no form request for delete — gate in controller
        Gate::authorize('delete', $room);

        $roomService->delete($room);

        return response()->json([], 204);
    }
}
