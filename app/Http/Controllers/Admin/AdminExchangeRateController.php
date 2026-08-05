<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExchangeRate\AdminUpdateExchangeRateRequest;
use App\Models\ExchangeRate;
use App\Services\Admin\AdminExchangeRateService;
use App\Services\PriceUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminExchangeRateController extends Controller
{
    public function __construct(
        public AdminExchangeRateService $exchangeRateService,
        public PriceUserService $priceUserService,
    ) {
    }

    public function index(): JsonResponse
    {
        $rates = ExchangeRate::orderBy('currency')->get();

        return $this->succeed(__('Exchange rates retrieved'), [
            'exchange_rates' => $rates,
        ]);
    }

    public function show(ExchangeRate $exchangeRate): JsonResponse
    {
        return $this->succeed(__('Exchange rate details'), [
            'exchange_rate' => $exchangeRate,
        ]);
    }

    public function store(AdminUpdateExchangeRateRequest $request): JsonResponse
    {
        $rate = $this->exchangeRateService->updateOrCreateRate($request->validated());

        return $this->succeed(__('Exchange rate saved'), [
            'exchange_rate' => $rate,
        ], 201);
    }

    public function update(AdminUpdateExchangeRateRequest $request, ExchangeRate $exchangeRate): JsonResponse
    {
        $data = $request->validated();
        $data['currency'] = $data['currency'] ?? $exchangeRate->currency;

        $rate = $this->exchangeRateService->updateOrCreateRate($data);

        return $this->succeed(__('Exchange rate updated'), [
            'exchange_rate' => $rate,
        ]);
    }

    public function destroy(ExchangeRate $exchangeRate): JsonResponse
    {
        $this->exchangeRateService->deleteRate($exchangeRate);

        return response()->json(null, 204);
    }

    public function bulkUpsert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rates' => ['required', 'array', 'min:1'],
            'rates.*.currency' => ['required', 'string', 'max:3'],
            'rates.*.rate_to_syp' => ['required', 'numeric', 'min:0'],
        ]);

        $results = $this->exchangeRateService->bulkUpsert($validated['rates']);

        return $this->succeed(__('Exchange rates saved'), [
            'exchange_rates' => $results,
        ], 201);
    }

    public function clearCaches(): JsonResponse
    {
        $this->priceUserService->clearExchangeRatesCaches();
        $this->priceUserService->clearPricesCaches();

        return $this->succeed(__('Exchange rate caches cleared'));
    }
}
