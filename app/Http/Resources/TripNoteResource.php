<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TripNoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'description' => $this->description,
            'pictures' => PictureResource::collection($this->getMedia('trip_note_pictures')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
