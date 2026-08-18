<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\TripListResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminGuideRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'trip' => $this->whenLoaded('trip', fn () => new TripListResource($this->trip)),
            'requester' => $this->when(
                $this->relationLoaded('trip') && $this->trip->relationLoaded('owner'),
                fn () => [
                    'id' => $this->trip->owner->id,
                    'name' => $this->trip->owner->name,
                    'email' => $this->trip->owner->email,
                ],
            ),
            'tourist_guide' => $this->whenLoaded('touristGuide', fn () => new AdminTouristGuideResource($this->touristGuide)),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'note' => $this->note,
            'responded_at' => $this->responded_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
