<?php

namespace App\Services\Admin;

use App\Models\Price;
use App\Models\Season;
use App\Services\PriceUserService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminPriceService
{
    public function __construct(
        private readonly PriceUserService $priceUserService
    ) {
    }

    public function storePrice(Model $priceable, array $data): Price
    {
        return DB::transaction(function () use ($priceable, $data) {
            $this->ensureSeasonMatchesPriceable($priceable, $data['season_id'] ?? null);

            $price = $priceable->prices()->create([
                'price_type' => $data['price_type'],
                'nationality_category' => $data['nationality_category'],
                'currency' => $data['currency'],
                'amount' => $data['amount'],
                'season_id' => $data['season_id'] ?? null,
            ]);

            $this->flushRelatedCaches($priceable, $price);

            return $price;
        });
    }

    public function updatePrice(Price $price, array $data): Price
    {
        return DB::transaction(function () use ($price, $data) {
            $originalSeasonId = $price->season_id;
            $priceable = $price->priceable;
            $this->ensureSeasonMatchesPriceable($priceable, $data['season_id'] ?? null);
            $originalNationality = $price->nationality_category;
            $originalPriceType = $price->price_type;
            $updates = array_filter([
                'price_type' => $data['price_type'] ?? null,
                'nationality_category' => $data['nationality_category'] ?? null,
                'currency' => $data['currency'] ?? null,
                'amount' => $data['amount'] ?? null,
            ], fn ($value) => $value !== null);

            if (array_key_exists('season_id', $data)) {
                $updates['season_id'] = $data['season_id'];
            }

            $price->update($updates);

            $this->flushRelatedCaches($priceable, $price);

            if (
                $originalSeasonId !== $price->season_id ||
                $originalNationality !== $price->nationality_category ||
                $originalPriceType !== $price->price_type
            ) {
                $this->flushRelatedCaches($priceable, $price, true);
            }

            return $price->fresh();
        });
    }

    public function upsertPriceTier(Model $priceable, array $data): Price
    {
        return DB::transaction(function () use ($priceable, $data) {
            $existing = $priceable->prices()
                ->where('price_type', $data['price_type'])
                ->where('nationality_category', $data['nationality_category'])
                ->whereRaw('season_id <=> ?', [$data['season_id'] ?? null])
                ->first();

            if ($existing) {
                return $this->updatePrice($existing, $data);
            }

            return $this->storePrice($priceable, $data);
        });
    }

    public function deletePrice(Price $price): void
    {
        DB::transaction(function () use ($price) {
            $priceable = $price->priceable;
            Price::destroy($price->getKey());

            $this->flushRelatedCaches($priceable, $price);
        });
    }

    public function bulkUpsert(Model $priceable, array $tiers): array
    {
        return DB::transaction(function () use ($priceable, $tiers) {
            $results = [];
            foreach ($tiers as $tier) {
                $results[] = $this->upsertPriceTier($priceable, $tier);
            }
            return $results;
        });
    }

    private function ensureSeasonMatchesPriceable(Model $priceable, ?int $seasonId): void
    {
        if ($seasonId === null) {
            return;
        }

        $season = Season::query()->find($seasonId);

        if (!$season || !$season->isFor($priceable)) {
            throw ValidationException::withMessages([
                'season_id' => [__('The selected season is not related to this priceable target.')],
            ]);
        }
    }

    private function flushRelatedCaches(Model $priceable, Price $price, bool $deep = false): void
    {
        $this->priceUserService->clearPricesCaches($priceable);

        if ($deep || $price->season_id !== null) {
            $this->priceUserService->clearSeasonsCaches();
        }

        if ($deep) {
            $this->priceUserService->clearExchangeRatesCaches();
        }
    }

    private function forgetLegacyCacheKeys(string $currency, ?int $seasonId): void
    {
        Cache::forget('exchange_rate_' . $currency);

        if ($seasonId) {
            $today = now()->format('Y-m-d');
            Cache::forget("season:price:lookup:{$today}:{$seasonId}");
        }
    }
}
