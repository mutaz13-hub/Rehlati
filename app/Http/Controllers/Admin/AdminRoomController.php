<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminRoomResource;
use App\Http\Resources\Admin\AdminAmenityResource;
use App\Http\Requests\Admin\Room\StoreRoomRequest;
use App\Http\Requests\Admin\Room\UpdateRoomRequest;
use App\Models\Hotel;
use App\Models\Room;
use App\Services\Admin\AdminRoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AdminRoomController extends Controller
{
    public function __construct(public AdminRoomService $room_service)
    {
    }

    public function index(Hotel $hotel)
    {
        $rooms = $hotel->rooms()->with(['description', 'amenities'])->paginate(15);

        return AdminRoomResource::collection($rooms);
    }

    public function show(Room $room)
    {
        return new AdminRoomResource($room->load(['hotel.location', 'description', 'amenities']));
    }

    public function amenities(Room $room)
    {
        $room->load('amenities');

        return $this->succeed(__('Amenities retrieved successfully'), [
            'amenities' => AdminAmenityResource::collection($room->amenities)
        ]);
    }

    public function store(StoreRoomRequest $request, Hotel $hotel): JsonResponse
    {
        $room = $this->room_service->create($hotel, $request->validated());

        return $this->succeed(__('Room created'), new AdminRoomResource($room));
    }

    public function update(UpdateRoomRequest $request, Hotel $hotel, Room $room): JsonResponse
    {
        $room = $this->room_service->update($room, $request->validated());

        return $this->succeed(__('Room updated'), new AdminRoomResource($room));
    }

    public function destroy(Hotel $hotel, Room $room): JsonResponse
    {
        Gate::authorize('delete', $room);

        $this->room_service->delete($room);

        return response()->json([], 204);
    }
}
