<?php

namespace App\Services\Payment;

interface PaymentStrategyInterface
{
    /**
     * Create a payment intent for the given amount and currency.
     *
     * @return array{id: string, client_secret: string, status: string}
     */
    public function createPaymentIntent(float $amount, string $currency, array $metadata = []): array;

    /**
     * Retrieve a payment intent by its ID.
     *
     * @return array{id: string, status: string, amount: float, currency: string}
     */
    public function retrievePaymentIntent(string $paymentIntentId): array;
}
