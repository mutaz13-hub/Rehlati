<?php

namespace App\Http\RateLimiters;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class VerificationRateLimiter implements RateLimiterInterface
{
    public function define(): void
    {
        RateLimiter::for('verify-email', function (Request $request) {
            return Limit::perMinutes(5, 3)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    $this->logRateLimited('verify-email', [
                        'user_id' => $request->user()?->id,
                        'user_agent' => $request->userAgent(),
                        'ip' => maskIp($request->ip()),
                        'retry_after' => (int) ($headers['Retry-After'] ?? 60),
                    ]);

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
                    $this->logRateLimited('resend-verification-code', [
                        'user_id' => $request->user()?->id,
                        'user_agent' => $request->userAgent(),
                        'ip' => maskIp($request->ip()),
                        'retry_after' => (int) ($headers['Retry-After'] ?? 60),
                    ]);

                    $message = $this->throttleMessage($headers['Retry-After'] ?? 60);
                    throw new ThrottleRequestsException(
                        $message,
                        null,
                        $headers
                    );
                });
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function logRateLimited(string $action, array $data): void
    {
        Log::channel('auth')->warning("Rate limit exceeded for {$action}", $data);
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
