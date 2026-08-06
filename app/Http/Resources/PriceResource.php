<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PriceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'price_type' => $this->price_type,
            'nationality_category' => $this->nationality_category,
            'currency' => $this->currency,
            'amount' => (float) $this->amount,
        ];
    }
}
