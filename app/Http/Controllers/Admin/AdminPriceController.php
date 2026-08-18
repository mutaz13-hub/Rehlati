<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Price\AdminStorePriceRequest;
use App\Http\Requests\Admin\Price\AdminUpdatePriceRequest;
use App\Http\Resources\Admin\AdminPriceResource;
use App\Models\Car;
use App\Models\CarAgency;
use App\Models\Hotel;
use App\Models\Package;
use App\Models\Price;
use App\Models\Room;
use App\Services\Admin\AdminPriceService;
use App\Services\PriceUserService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPriceController extends Controller
{
    public function __construct(
        public AdminPriceService $priceService,
        public PriceUserService $priceUserService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Price::query()->with(['season', 'priceable']);

        if ($request->filled('priceable_type') && $request->filled('priceable_id')) {
            $query->where('priceable_type', $request->input('priceable_type'))
                ->where('priceable_id', $request->input('priceable_id'));
        }

        if ($request->filled('nationality_category')) {
            $query->where('nationality_category', $request->input('nationality_category'));
        }

        if ($request->filled('price_type')) {
            $query->where('price_type', $request->input('price_type'));
        }

        $data = $query->orderBy('season_id', 'desc')
            
            ->paginate(30);

        return $this->succeed(__('Prices retrieved'), [
            'data' => AdminPriceResource::collection($data),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'total' => $data->total()
            ]
        ]);
    }

    public function show(Price $price): JsonResponse
    {
        return $this->succeed(__('Price details'), [
            'price' => new AdminPriceResource($price->load(['season', 'priceable'])),
        ]);
    }

    public function store(AdminStorePriceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $priceable = $this->resolvePriceable(
            $validated['priceable_type'],
            $validated['priceable_id']
        );

        if (! $priceable) {
            return $this->failed(__('Invalid priceable target'), 422);
        }

        $this->priceService->upsertPriceTier($priceable, $validated);

        return $this->succeed(__('Price saved'), [], 201);
    }

    public function update(AdminUpdatePriceRequest $request, Price $price): JsonResponse
    {
        $this->priceService->updatePrice($price, $request->validated());

        return $this->succeed(__('Price updated'));
    }

    public function destroy(Price $price): JsonResponse
    {
        $this->priceService->deletePrice($price);

        return $this->succeed(__('Price deleted'));
    }

    public function bulkUpsert(Request $request): JsonResponse
    {
        $request->validate([
            'priceable_type' => ['required', 'string'],
            'priceable_id' => ['required', 'integer'],
            'tiers' => ['required', 'array', 'min:1'],
            'tiers.*.price_type' => ['required', 'string', 'in:base_price,child_price,extra_bed_price,package_price'],
            'tiers.*.nationality_category' => ['required', 'string', 'in:syrian,expat,foreigner'],
            'tiers.*.amount' => ['required', 'numeric', 'min:0'],
            'tiers.*.season_id' => ['nullable', 'exists:seasons,id'],
        ]);

        $priceable = $this->resolvePriceable(
            $request->input('priceable_type'),
            $request->input('priceable_id')
        );

        if (! $priceable) {
            return $this->failed(__('Invalid priceable target'), 422);
        }

        $this->priceService->bulkUpsert($priceable, $request->input('tiers'));

        return $this->succeed(__('Price tiers saved'), [], 201);
    }

    public function clearCaches(): JsonResponse
    {
        $this->priceUserService->refreshAllCaches();

        return $this->succeed(__('Pricing caches cleared'));
    }

    private function resolvePriceable(string $type, int $id): ?Model
    {
        $morphMap = [
            'room' => Room::class,
            'hotel' => Hotel::class,
            'car' => Car::class,
            'car_agency' => CarAgency::class,
            'package' => Package::class,
        ];

        $class = $morphMap[$type] ?? $type;

        if (! class_exists($class)) {
            return null;
        }

        return $class::find($id);
    }
}
