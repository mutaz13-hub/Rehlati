<?php

namespace App\Services;

use App\Services\Payment\PaymentStrategyInterface;
use Stripe\Exception\ApiErrorException;

class PaymentService
{
    public function __construct(
        private readonly PaymentStrategyInterface $strategy
    ) {}

    /**
     * @return array{id: string, client_secret: string, status: string}
     *
     * @throws ApiErrorException
     */
    public function createPaymentIntent(float $amount, string $currency, array $metadata = []): array
    {
        return $this->strategy->createPaymentIntent($amount, $currency, $metadata);
    }

    /**
     * @return array{id: string, status: string, amount: float, currency: string}
     *
     * @throws ApiErrorException
     */
    public function retrievePaymentIntent(string $paymentIntentId): array
    {
        return $this->strategy->retrievePaymentIntent($paymentIntentId);
    }
}
