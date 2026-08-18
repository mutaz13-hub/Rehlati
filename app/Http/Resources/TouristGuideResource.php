<?php

namespace App\Http\Resources;

use App\Services\PriceUserService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TouristGuideResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $priceService = app(PriceUserService::class);
        $activeCurrency = $priceService->getActiveCurrency();
        $basePrice = $this->price_per_hour !== null ? (float) $this->price_per_hour : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'availability' => $this->availability,
            'price_per_hour' => $basePrice !== null
                ? $priceService->convertToCurrency($basePrice, PriceUserService::BASE_CURRENCY, $activeCurrency)
                : null,
            'currency' => $activeCurrency,
            'average_rating' => $this->average_rating ?? 0,
            'total_reviews' => $this->total_reviews ?? 0,
        ];
    }
}
