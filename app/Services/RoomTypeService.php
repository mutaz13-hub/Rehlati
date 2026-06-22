<?php

namespace App\Services;

use App\Models\RoomType;

class RoomTypeService
{
    public function create(array $data): RoomType
    {
        return RoomType::create($data);
    }

    public function update(RoomType $roomType, array $data): RoomType
    {
        $roomType->update($data);

        return $roomType;
    }

    public function delete(RoomType $roomType): void
    {
        $roomType->delete();
    }
}
