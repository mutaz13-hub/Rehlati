<?php

namespace App\Providers;

use App\Models\City;
use App\Models\Hotel;
use App\Models\Rating;
use App\Models\Region;
use App\Models\Room;
use App\Models\User;
use App\Models\Vote;
use App\Observers\VoteObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
        JsonResource::withoutWrapping();

        Model::preventLazyLoading(! app()->isProduction());

        Relation::morphMap([
            User::MORPH_KEY => User::class,
            Hotel::MORPH_KEY => Hotel::class,
            Room::MORPH_KEY => Room::class,
            City::MORPH_KEY => City::class,
            Region::MORPH_KEY => Region::class,
            Rating::MORPH_KEY => Rating::class,
        ]);

        Vote::observe(VoteObserver::class);
    }
}
