<?php

namespace App\Http\Controllers\Mutual;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoomTypeResource;
use App\Http\Requests\RoomType\StoreRoomTypeRequest;
use App\Http\Requests\RoomType\UpdateRoomTypeRequest;
use App\Models\RoomType;
use App\Services\RoomTypeService;
use Illuminate\Http\JsonResponse;

class RoomTypeController extends Controller
{
    public function index()
    {
        $roomTypes = RoomType::paginate(20);

        return RoomTypeResource::collection($roomTypes);
    }

    public function show(RoomType $roomType)
    {
        return new RoomTypeResource($roomType);
    }

    public function store(StoreRoomTypeRequest $request, RoomTypeService $roomTypeService): JsonResponse
    {
        $roomType = $roomTypeService->create($request->validated());

        return $this->succeed(__('Room type created'), new RoomTypeResource($roomType));
    }

    public function update(UpdateRoomTypeRequest $request, RoomType $roomType, RoomTypeService $roomTypeService): JsonResponse
    {
        $roomType = $roomTypeService->update($roomType, $request->validated());

        return $this->succeed(__('Room type updated'), new RoomTypeResource($roomType));
    }

    public function destroy(RoomType $roomType, RoomTypeService $roomTypeService): JsonResponse
    {
        $this->authorize('delete', $roomType);

        $roomTypeService->delete($roomType);

        return response()->json([], 204);
    }
}
