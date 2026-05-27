<?php

use App\Http\RateLimiters\AuthenticationRateLimiter;
use App\Http\RateLimiters\VerificationRateLimiter;
use App\Http\RateLimiters\ForgotPasswordRateLimiter;

return [
    AuthenticationRateLimiter::class,
    VerificationRateLimiter::class,
    ForgotPasswordRateLimiter::class,
];
