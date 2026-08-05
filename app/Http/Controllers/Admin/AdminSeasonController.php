<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Season\AdminStoreSeasonRequest;
use App\Http\Requests\Admin\Season\AdminUpdateSeasonRequest;
use App\Models\Season;
use App\Services\Admin\AdminSeasonService;
use App\Services\PriceUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSeasonController extends Controller
{
    public function __construct(
        public AdminSeasonService $seasonService,
        public PriceUserService $priceUserService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Season::query()->withCount('prices');

        if ($request->boolean('active_only')) {
            $today = now()->format('Y-m-d');
            $query->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today);
        }

        if ($request->boolean('upcoming')) {
            $query->where('end_date', '>=', now()->format('Y-m-d'));
        }

        $seasons = $query->orderBy('start_date')->paginate(30);

        return $this->succeed(__('Seasons retrieved'), [
            'seasons' => $seasons,
        ]);
    }

    public function show(Season $season): JsonResponse
    {
        return $this->succeed(__('Season details'), [
            'season' => $season->loadCount('prices'),
        ]);
    }

    public function store(AdminStoreSeasonRequest $request): JsonResponse
    {
        $season = $this->seasonService->store($request->validated());

        return $this->succeed(__('Season created'), [
            'season' => $season,
        ], 201);
    }

    public function update(AdminUpdateSeasonRequest $request, Season $season): JsonResponse
    {
        $updated = $this->seasonService->update($season, $request->validated());

        return $this->succeed(__('Season updated'), [
            'season' => $updated,
        ]);
    }

    public function destroy(Season $season): JsonResponse
    {
        $this->seasonService->delete($season);

        return response()->json(null, 204);
    }

    public function current(): JsonResponse
    {
        $season = $this->priceUserService->resolveActiveSeason();

        return $this->succeed(__('Current season'), [
            'season' => $season,
        ]);
    }

    public function clearCaches(): JsonResponse
    {
        $this->seasonService->clearAllCache();

        return $this->succeed(__('Season caches cleared'));
    }
}
