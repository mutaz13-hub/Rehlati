<?php

namespace App\Http\RateLimiters;

interface RateLimiterInterface
{
    public function define(): void;

    /**
     * @param array<string, mixed> $data
     */
    public function logRateLimited(string $action, array $data): void;
}
