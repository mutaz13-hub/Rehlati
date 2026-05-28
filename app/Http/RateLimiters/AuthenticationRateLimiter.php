<?php

namespace App\Http\RateLimiters;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AuthenticationRateLimiter implements RateLimiterInterface
{
    public function define(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)
                ->by('login|'.$request->input('email').'|'.$request->ip())
                ->response(function (Request $request, array $headers) {
                    $this->logRateLimited('login', [
                        'email' => maskEmail($request->input('email')),
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

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)
                ->by('register|'.$request->input('email').'|'.$request->ip())
                ->response(function (Request $request, array $headers) {
                    $this->logRateLimited('register', [
                        'email' => maskEmail($request->input('email')),
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

        RateLimiter::for('google-login', function (Request $request) {
            return Limit::perMinute(5)
                ->by('google-login|'.$request->ip())
                ->response(function (Request $request, array $headers) {
                    $this->logRateLimited('google-login', [
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

        RateLimiter::for('refresh', function (Request $request) {
            return Limit::perMinute(10)
                ->by('refresh|'.$request->header('device').'|'.$request->ip())
                ->response(function (Request $request, array $headers) {
                    $this->logRateLimited('refresh', [
                        'device' => $request->header('device'),
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
