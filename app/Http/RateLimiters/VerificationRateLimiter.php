<?php

namespace App\Http\RateLimiters;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class VerificationRateLimiter implements RateLimiterInterface
{
    public function define(): void
    {
        RateLimiter::for('verify-email', function (Request $request) {
            return Limit::perMinutes(5, 3)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    $message = $this->throttleMessage($headers['Retry-After'] ?? 60);
                    throw new ThrottleRequestsException(
                        $message,
                        null,
                        $headers
                    );
                });
        });

        RateLimiter::for('resend-verification-code', function (Request $request) {
            return Limit::perMinutes(10, 3)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    $message = $this->throttleMessage($headers['Retry-After'] ?? 60);
                    throw new ThrottleRequestsException(
                        $message,
                        null,
                        $headers
                    );
                });
        });
    }

    private function throttleMessage(int $seconds): string
    {
        if ($seconds <= 60) {
            return __('auth.throttle', ['seconds' => $seconds]);
        }

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        if ($remainingSeconds === 0) {
            return __('auth.throttle_minutes_only', ['minutes' => $minutes]);
        }

        return __('auth.throttle_minutes', [
            'minutes' => $minutes,
            'seconds' => $remainingSeconds,
        ]);
    }
}
