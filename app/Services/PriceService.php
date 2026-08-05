<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PriceService
{
    public function __construct(
        private readonly PriceUserService $priceUserService
    ) {
    }

    public function calculateFinalPrice(Model $priceable, $user, string $priceType = 'base_price'): ?float
    {
        if ($user instanceof User) {
            return $this->priceUserService->calculateFinalPriceValue($priceable, $user, $priceType);
        }

        $userModel = new User();
        if (is_object($user) && isset($user->nationality_category)) {
            $userModel->nationality_category = $user->nationality_category;
        }
        if (is_object($user) && isset($user->nationality)) {
            $userModel->nationality = $user->nationality;
        }

        return $this->priceUserService->calculateFinalPriceValue($priceable, $userModel, $priceType);
    }

    public function convertToSYP(float $amount, string $currency): float
    {
        return $this->priceUserService->convertToSyp($amount, $currency);
    }
}
