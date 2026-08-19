<?php

namespace App\Services\Payment;

use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripePaymentStrategy implements PaymentStrategyInterface
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * @throws ApiErrorException
     */
    public function createPaymentIntent(float $amount, string $currency, array $metadata = []): array
    {
        $intent = PaymentIntent::create([
            'amount' => $this->formatAmount($amount, $currency),
            'currency' => strtolower($currency),
            'metadata' => $metadata,
        ]);

        return [
            'id' => $intent->id,
            'client_secret' => $intent->client_secret,
            'status' => $intent->status,
        ];
    }

    /**
     * @throws ApiErrorException
     */
    public function retrievePaymentIntent(string $paymentIntentId): array
    {
        $intent = PaymentIntent::retrieve($paymentIntentId);

        return [
            'id' => $intent->id,
            'status' => $intent->status,
            'amount' => $intent->amount / 100,
            'currency' => strtoupper($intent->currency),
        ];
    }

    private function formatAmount(float $amount, string $currency): int
    {
        $zeroDecimalCurrencies = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];

        if (in_array(strtoupper($currency), $zeroDecimalCurrencies)) {
            return (int) $amount;
        }

        return (int) round($amount * 100);
    }
}
