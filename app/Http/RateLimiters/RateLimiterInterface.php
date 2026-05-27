<?php

namespace App\Http\RateLimiters;

interface RateLimiterInterface
{
    public function define(): void;
}
