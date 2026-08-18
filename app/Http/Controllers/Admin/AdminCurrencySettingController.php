<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CurrencySetting\UpdateCurrencySettingRequest;
use App\Services\AppSettingService;
use App\Services\PriceUserService;
use Illuminate\Http\JsonResponse;

class AdminCurrencySettingController extends Controller
{
    public function __construct(
        private readonly AppSettingService $settingService,
        private readonly PriceUserService $priceUserService,
    ) {}

    public function index(): JsonResponse
    {
        return $this->succeed(__('Currency settings retrieved'), [
            'base_currency' => PriceUserService::BASE_CURRENCY,
            'active_currency' => $this->settingService->get('active_currency', PriceUserService::BASE_CURRENCY),
            'exchange_rate' => $this->priceUserService->getExchangeRate(PriceUserService::BASE_CURRENCY),
        ]);
    }

    public function update(UpdateCurrencySettingRequest $request): JsonResponse
    {
        $this->settingService->set('active_currency', strtoupper($request->input('active_currency')));

        $this->priceUserService->clearPricesCaches();

        return $this->succeed(__('Active currency updated'), [
            'base_currency' => PriceUserService::BASE_CURRENCY,
            'active_currency' => strtoupper($request->input('active_currency')),
        ]);
    }
}
