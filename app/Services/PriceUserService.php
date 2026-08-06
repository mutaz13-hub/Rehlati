<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Models\Season;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;

class PriceUserService
{
    public const CACHE_SEASONS_TTL = 3600;
    public const CACHE_RATES_TTL = 86400;
    public const CACHE_PRICE_LOOKUP_TTL = 600;

    public function calculateFinalPrice(
        Model $priceable,
        User $user,
        string $priceType = 'base_price',
        ?\DateTimeInterface $forDate = null
    ): ?array {
        $forDate = Date::parse($forDate ?? now());

        $nationalityCategory = $user->resolved_nationality_category;

        $season = $this->resolveActiveSeason($priceable, $forDate);

        $price = $this->lookupMatchingPrice(
            $priceable,
            $priceType,
            $nationalityCategory,
            $season?->id
        );

        if (!$price) {
            return null;
        }

        $amountInSyp = $this->convertToSyp((float) $price->amount, $price->currency);

        return [
            'amount' => round($amountInSyp, 2),
            'currency' => 'SYP',
            'base_amount' => (float) $price->amount,
            'base_currency' => $price->currency,
            'season_id' => $season?->id,
            'season_name' => $season?->name_en ?? $season?->name_ar,
            'nationality_category' => $nationalityCategory,
            'price_type' => $priceType,
        ];
    }

    public function calculateFinalPriceValue(
        Model $priceable,
        User $user,
        string $priceType = 'base_price',
        ?\DateTimeInterface $forDate = null
    ): ?float {
        $result = $this->calculateFinalPrice($priceable, $user, $priceType, $forDate);

        return $result ? $result['amount'] : null;
    }

    public function resolveActiveSeason($priceableOrDate = null, ?\DateTimeInterface $forDate = null): ?Season
    {
        $priceable = null;

        if ($priceableOrDate instanceof Model) {
            $priceable = $priceableOrDate;
        } elseif ($priceableOrDate instanceof \DateTimeInterface) {
            $forDate = $priceableOrDate;
        }

        $forDate = Date::parse($forDate ?? now());
        $dateStr = $forDate->format('Y-m-d');

        $cacheKey = $priceable
            ? "active_season:{$priceable->getMorphClass()}:{$priceable->getKey()}:{$dateStr}"
            : "active_season:{$dateStr}";

        return Cache::remember($cacheKey, self::CACHE_SEASONS_TTL, function () use ($priceable, $dateStr) {
            $query = Season::query()->where('start_date', '<=', $dateStr)
                ->where('end_date', '>=', $dateStr);

            if ($priceable) {
                $query->where('seasonable_type', $priceable->getMorphClass())
                    ->where('seasonable_id', $priceable->getKey());
            }

            return $query->orderBy('start_date', 'desc')->first();
        });
    }

    public function lookupMatchingPrice(
        Model $priceable,
        string $priceType,
        string $nationalityCategory,
        ?int $seasonId = null
    ) {
        $priceableKey = $this->priceableCacheKey($priceable);
        $cacheKey = "price:{$priceableKey}:{$priceType}:{$nationalityCategory}:{$seasonId}";

        return Cache::remember($cacheKey, self::CACHE_PRICE_LOOKUP_TTL, function () use (
            $priceable,
            $priceType,
            $nationalityCategory,
            $seasonId
        ) {
            if ($seasonId !== null) {
                $season = Season::query()->find($seasonId);

                if (!$season || !$season->isFor($priceable)) {
                    return null;
                }
            }

            $query = $priceable->prices()
                ->where('price_type', $priceType)
                ->where('nationality_category', $nationalityCategory);

            if ($seasonId !== null) {
                $query->where(function ($q) use ($seasonId) {
                    $q->where('season_id', $seasonId)
                        ->orWhereNull('season_id');
                });
                $query->orderByRaw('season_id IS NULL ASC');
            } else {
                $query->whereNull('season_id');
            }

            return $query->first();
        });
    }

    public function getAllMatchingPrices(
        Model $priceable,
        User $user,
        ?\DateTimeInterface $forDate = null
    ): Collection {
        $forDate = Date::parse($forDate ?? now());
        $nationalityCategory = $user->resolved_nationality_category;
        $season = $this->resolveActiveSeason($priceable, $forDate);

        $priceableKey = $this->priceableCacheKey($priceable);
        $cacheKey = "all_prices:{$priceableKey}:{$nationalityCategory}:{$season?->id}";

        return Cache::remember($cacheKey, self::CACHE_PRICE_LOOKUP_TTL, function () use (
            $priceable,
            $nationalityCategory,
            $season
        ) {
            $query = $priceable->prices()
                ->where('nationality_category', $nationalityCategory);

            if ($season) {
                $query->where(function ($q) use ($season) {
                    $q->where('season_id', $season->id)
                        ->orWhereNull('season_id');
                });
            } else {
                $query->whereNull('season_id');
            }

            $rows = $query->get();

            $deduped = collect();
            foreach ($rows->groupBy('price_type') as $type => $group) {
                $seasonal = $group->firstWhere('season_id', $season?->id);
                $deduped->push($seasonal ?? $group->firstWhere('season_id', null));
            }

            return $deduped->values();
        });
    }

    public function convertToSyp(float $amount, string $currency): float
    {
        $currency = strtoupper($currency);

        if ($currency === 'SYP') {
            return $amount;
        }

        $rate = $this->getExchangeRate($currency);

        return round($amount * $rate, 2);
    }

    public function getExchangeRate(string $currency): float
    {
        $currency = strtoupper($currency);
        $cacheKey = "exchange_rate:{$currency}";

        return Cache::remember($cacheKey, self::CACHE_RATES_TTL, function () use ($currency) {
            $rate = ExchangeRate::query()->where('currency', $currency)->first();

            return $rate ? (float) $rate->rate_to_syp : 1.0;
        });
    }

    public function refreshAllCaches(): void
    {
        $this->clearSeasonsCaches();
        $this->clearExchangeRatesCaches();
        $this->clearPricesCaches();
    }

    public function clearSeasonsCaches(): void
    {
        $dates = collect();
        $start = now()->startOfMonth();
        for ($i = 0; $i < 366; $i++) {
            $dates->push($start->clone()->addDays($i)->format('Y-m-d'));
        }

        foreach ($dates as $date) {
            Cache::forget("active_season:{$date}");
        }

        Cache::forget('seasons:all:active');
    }

    public function clearExchangeRatesCaches(): void
    {
        foreach (['USD', 'EUR', 'GBP', 'AED', 'SAR', 'TRY', 'EGP', 'JOD', 'LBP', 'IQD'] as $currency) {
            Cache::forget("exchange_rate:{$currency}");
        }
    }

    public function clearPricesCaches(?Model $priceable = null): void
    {
        if ($priceable) {
            $prefix = 'price:' . $this->priceableCacheKey($priceable);
            $this->forgetCacheByPrefix($prefix);
            $this->forgetCacheByPrefix('all_prices:' . $this->priceableCacheKey($priceable));
            return;
        }

        $this->forgetCacheByPrefix('price:');
        $this->forgetCacheByPrefix('all_prices:');
    }

    private function priceableCacheKey(Model $priceable): string
    {
        $type = method_exists($priceable, 'getMorphClass')
            ? $priceable->getMorphClass()
            : get_class($priceable);

        return "{$type}:{$priceable->getKey()}";
    }

    private function forgetCacheByPrefix(string $prefix): void
    {
        Cache::flush();
    }
}
