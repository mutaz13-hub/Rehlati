<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminPriceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'price_type' => $this->price_type,
            'nationality_category' => $this->nationality_category,
            'currency' => $this->currency,
            'amount' => (float) $this->amount,
            'season' => $this->when($this->relationLoaded('season') && $this->season, function () {
                return new AdminSeasonResource($this->season);
            }),
        ];
    }
}
