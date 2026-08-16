<?php

namespace App\Http\Resources;

use App\Enums\TripStatus;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $role = $this->roleFor(auth('sanctum')->user());

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'start_date' => $this->start_date?->toDateString(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'role' => $role,
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
            ]),
            'cities' => TripCityResource::collection($this->whenLoaded('cities')),
            'notes' => TripNoteResource::collection($this->whenLoaded('notes')),
            'route_polyline' => $this->status === TripStatus::FINISHED ? $this->route_polyline : null,
        ];
    }
}
