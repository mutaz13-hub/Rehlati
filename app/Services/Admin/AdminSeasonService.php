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

    public function store(array $data): Season
    {
        return DB::transaction(function () use ($data) {
            $season = Season::create([
                'name' => $data['name'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
            ]);

            $this->priceUserService->clearSeasonsCaches();
            $this->priceUserService->clearPricesCaches();

            return $season;
        });
    }

    public function update(Season $season, array $data): Season
    {
        return DB::transaction(function () use ($season, $data) {
            $season->update(array_filter([
                'name' => $data['name'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
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
            $season->delete();

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
