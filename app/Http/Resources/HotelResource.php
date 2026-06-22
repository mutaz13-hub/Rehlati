<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HotelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->localized_name,
            'stars' => $this->stars,
            'city' => new CityResource($this->whenLoaded('city')),
            'rooms_count' => $this->whenLoaded('rooms') ? $this->rooms->count() : $this->rooms()->count(),
            'location' => $this->whenLoaded('location') ? $this->location : null,
        ];
    }
}
