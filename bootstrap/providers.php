<?php

use App\Providers\AppServiceProvider;
use App\Providers\CityServiceProvider;
use App\Providers\RateLimitingServiceProvider;

return [
    AppServiceProvider::class,
    CityServiceProvider::class,
    RateLimitingServiceProvider::class,
];
