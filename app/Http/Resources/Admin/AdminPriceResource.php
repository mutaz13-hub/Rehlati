<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\CarAgencyResource;
use App\Models\CarAgency;
use App\Models\Hotel;
use App\Models\Package;
use App\Models\Room;
use App\Services\PriceUserService;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPriceResource extends JsonResource
{
    public function toArray($request): array
    {
        $priceService = app(PriceUserService::class);
        $activeCurrency = $priceService->getActiveCurrency();

        return [
            'id' => $this->id,
            'price_type' => $this->price_type,
            'nationality_category' => $this->nationality_category,
            'currency' => $this->currency,
            'amount' => (float) $this->amount,
            'active_currency' => $activeCurrency,
            'active_amount' => $priceService->convertToCurrency((float) $this->amount, $this->currency, $activeCurrency),
            'season' => $this->when($this->relationLoaded('season') && $this->season, function () {
                return new AdminSeasonResource($this->season);
            }),
            'priceable' => $this->when($this->relationLoaded('priceable') && $this->priceable, function () {
                return match (true) {
                    $this->priceable instanceof Hotel => new AdminHotelResource($this->priceable),
                    $this->priceable instanceof Room => new AdminRoomResource($this->priceable),
                    $this->priceable instanceof CarAgency => new CarAgencyResource($this->priceable),
                    $this->priceable instanceof Package => new AdminPackageResource($this->priceable),
                    default => null,
                };
            }),
        ];
    }
}
