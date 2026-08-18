<?php

namespace Database\Seeders;

use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class ExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            ['currency' => 'USD', 'rate_to_syp' => 130],
            ['currency' => 'EUR', 'rate_to_syp' => 1350],
            ['currency' => 'GBP', 'rate_to_syp' => 1910],
            ['currency' => 'SAR', 'rate_to_syp' => 40],
            ['currency' => 'AED', 'rate_to_syp' => 40],
            ['currency' => 'TRY', 'rate_to_syp' => 46],
        ];

        foreach ($rates as $rate) {
            ExchangeRate::updateOrCreate(
                ['currency' => $rate['currency']],
                ['rate_to_syp' => $rate['rate_to_syp']]
            );

            Cache::forget('exchange_rate:'.$rate['currency']);
        }
    }
}
