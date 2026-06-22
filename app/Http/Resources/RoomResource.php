<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'hotel_id' => $this->hotel_id,
            'room_type' => $this->whenLoaded('roomType') ? [
                'id' => $this->roomType->id,
                'name_en' => $this->roomType->name_en,
                'name_ar' => $this->roomType->name_ar,
            ] : null,
            'price_per_night' => $this->price_per_night,
            'total_rooms' => $this->total_rooms,
            'available_rooms' => $this->available_rooms,
        ];
    }
}
