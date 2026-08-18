<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\TouristGuideResource as BaseTouristGuideResource;
use App\Services\PriceUserService;
use Illuminate\Http\Request;

class AdminTouristGuideResource extends BaseTouristGuideResource
{
    public function toArray(Request $request): array
    {
        $priceService = app(PriceUserService::class);
        $activeCurrency = $priceService->getActiveCurrency();

        $data = array_merge(parent::toArray($request), [
            'email' => $this->email,
            'phone' => $this->phone,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toDateTimeString(),
        ]);

        $data['price_per_hour'] = $this->price_per_hour !== null ? (float) $this->price_per_hour : null;
        $data['currency'] = PriceUserService::BASE_CURRENCY;
        $data['active_currency'] = $activeCurrency;
        $data['active_price_per_hour'] = $this->price_per_hour !== null
            ? $priceService->convertToCurrency((float) $this->price_per_hour, PriceUserService::BASE_CURRENCY, $activeCurrency)
            : null;

        return $data;
    }
}
