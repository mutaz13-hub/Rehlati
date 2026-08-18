<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuideRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tourist_guide' => $this->whenLoaded('touristGuide', fn () => new TouristGuideResource($this->touristGuide)),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'note' => $this->note,
            'responded_at' => $this->responded_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
