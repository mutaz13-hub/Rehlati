<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TripListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'uuid' => $this->uuid,
            'start_date' => $this->start_date?->toDateString(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
        ];
    }
}
