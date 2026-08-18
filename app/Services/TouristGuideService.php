<?php

namespace App\Services;

use App\Models\TouristGuide;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TouristGuideService
{
    public function index(array $filters, int $perPage = 10, bool $onlyActive = false): LengthAwarePaginator
    {
        $query = TouristGuide::query()
            ->withCount('reviews')
            ->withAvg('reviews', 'rate');

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('is_active')->orderBy('name')->paginate($perPage);
    }

    public function listActive(array $filters): Collection
    {
        $query = TouristGuide::query()
            ->where('is_active', true)
            ->withCount('reviews')
            ->withAvg('reviews', 'rate');

        $this->applyFilters($query, $filters);

        return $query->orderBy('name')->get();
    }

    public function create(array $data): void
    {
        TouristGuide::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'availability' => $data['availability'] ?? null,
            'price_per_hour' => $data['price_per_hour'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(TouristGuide $guide, array $data): void
    {
        $guide->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'availability' => $data['availability'] ?? null,
            'price_per_hour' => $data['price_per_hour'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function delete(TouristGuide $guide): void
    {
        $guide->delete();
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['q'])) {
            $query->where(function (Builder $q) use ($filters) {
                $q->where('name', 'like', "%{$filters['q']}%")
                    ->orWhere('email', 'like', "%{$filters['q']}%")
                    ->orWhere('phone', 'like', "%{$filters['q']}%");
            });
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }
    }
}
