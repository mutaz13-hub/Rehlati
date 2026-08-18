<?php

namespace App\Services\Admin;

use App\Models\ExchangeRate;
use App\Services\PriceUserService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminExchangeRateService
{
    public function __construct(
        private readonly PriceUserService $priceUserService
    ) {}

    public function updateOrCreateRate(array $data): void
    {
        DB::transaction(function () use ($data) {
            $rate = ExchangeRate::updateOrCreate(
                ['currency' => strtoupper($data['currency'])],
                ['rate_to_syp' => $data['rate_to_syp']]
            );

            $this->priceUserService->clearExchangeRatesCaches();
            $this->priceUserService->clearPricesCaches();

            Cache::put(
                'exchange_rate:'.strtoupper($rate->currency),
                (float) $rate->rate_to_syp,
                PriceUserService::CACHE_RATES_TTL
            );
        });
    }

    public function bulkUpsert(array $rates): array
    {
        return DB::transaction(function () use ($rates) {
            $results = [];
            foreach ($rates as $rate) {
                $results[] = $this->updateOrCreateRate($rate);
            }

            return $results;
        });
    }

    public function deleteRate(ExchangeRate $rate): void
    {
        DB::transaction(function () use ($rate) {
            $currency = strtoupper($rate->currency);
            $rate->delete();

            $this->priceUserService->clearExchangeRatesCaches();
            $this->priceUserService->clearPricesCaches();

            Cache::forget('exchange_rate:'.$currency);
            Cache::forget('exchange_rate_'.$currency);
        });
    }
}
