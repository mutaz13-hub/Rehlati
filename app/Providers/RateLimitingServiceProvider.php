<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RateLimitingServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (config('rate_limiters', []) as $rateLimiter) {
            $this->app->make($rateLimiter)->define();
        }
    }
}
