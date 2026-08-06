<?php

namespace App\Services\Admin;

use App\Models\Season;
use App\Services\PriceUserService;
use Illuminate\Support\Facades\DB;

class AdminSeasonService
{
    public function __construct(
        private readonly PriceUserService $priceUserService
    ) {
    }

    public function store(array $data): void
    {
         DB::transaction(function () use ($data) {
            $season = Season::create([
                'name_en' => $data['name_en'],
                'name_ar' => $data['name_ar'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'seasonable_type' => $data['seasonable_type'] ?? null,
                'seasonable_id' => $data['seasonable_id'] ?? null,
            ]);

            $this->priceUserService->clearSeasonsCaches();
            $this->priceUserService->clearPricesCaches();
        });
    }

    public function update(Season $season, array $data): Season
    {
        return DB::transaction(function () use ($season, $data) {
            $season->update(array_filter([
                'name_en' => $data['name_en'] ?? null,
                'name_ar' => $data['name_ar'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'seasonable_type' => $data['seasonable_type'] ?? null,
                'seasonable_id' => $data['seasonable_id'] ?? null,
            ], fn ($value) => $value !== null));

            $this->priceUserService->clearSeasonsCaches();
            $this->priceUserService->clearPricesCaches();

            return $season->fresh();
        });
    }

    public function delete(Season $season): void
    {
        DB::transaction(function () use ($season) {
            $season->prices()->update(['season_id' => null]);
            $season->forceDelete();

            $this->priceUserService->clearSeasonsCaches();
            $this->priceUserService->clearPricesCaches();
        });
    }

    public function clearAllCache(): void
    {
        $this->priceUserService->clearSeasonsCaches();
        $this->priceUserService->clearPricesCaches();
    }
}
