<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TripDestinationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'order' => $this->order,
            'visited' => $this->visited_at !== null,
            'visited_at' => $this->visited_at?->toIso8601String(),
            'destination' => [
                'id' => $this->destinable->id,
                'type' => $this->destinable->getMorphClass(),
                'name' => $this->destinable->localized_name,
                'latitude' => $this->destinable->location ? (float) $this->destinable->location->latitude : null,
                'longitude' => $this->destinable->location ? (float) $this->destinable->location->longitude : null,
            ],
        ];
    }
}
