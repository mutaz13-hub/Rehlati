<?php

use App\Http\RateLimiters\AuthenticationRateLimiter;
use App\Http\RateLimiters\CommunityRateLimiter;
use App\Http\RateLimiters\ForgotPasswordRateLimiter;
use App\Http\RateLimiters\VerificationRateLimiter;

return [
    AuthenticationRateLimiter::class,
    VerificationRateLimiter::class,
    ForgotPasswordRateLimiter::class,
    CommunityRateLimiter::class,
];
