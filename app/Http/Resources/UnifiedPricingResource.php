<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Services\PriceUserService;
use Illuminate\Http\Resources\Json\JsonResource;

class UnifiedPricingResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $request->user();

        return app(PriceUserService::class)->unifiedPricing(
            $this->resource,
            $user instanceof User ? $user : null
        );
    }
}
