<?php

namespace App\Providers;

use App\Models\CarAgency;
use App\Models\City;
use App\Models\Comment;
use App\Models\Community;
use App\Models\Hotel;
use App\Models\Package;
use App\Models\Post;
use App\Models\Rating;
use App\Models\Region;
use App\Models\Room;
use App\Models\TripNote;
use App\Models\User;
use App\Models\Vote;
use App\Observers\CityObserver;
use App\Observers\CommentObserver;
use App\Observers\HotelObserver;
use App\Observers\PostObserver;
use App\Observers\RegionObserver;
use App\Observers\RoomObserver;
use App\Observers\VoteObserver;
use Dedoc\Scramble\Scramble;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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

        Scramble::configure()
            ->routes(function (Route $route) {
                // Adjust the string to match your exact admin URL prefix
                return Str::startsWith($route->uri, 'api/admin');
            });
        JsonResource::withoutWrapping();

        Model::preventLazyLoading(! app()->isProduction());

        Relation::morphMap([
            User::MORPH_KEY => User::class,
            Hotel::MORPH_KEY => Hotel::class,
            Room::MORPH_KEY => Room::class,
            City::MORPH_KEY => City::class,
            Region::MORPH_KEY => Region::class,
            Rating::MORPH_KEY => Rating::class,
            Package::MORPH_KEY => Package::class,
            CarAgency::MORPH_KEY => CarAgency::class,
            TripNote::MORPH_KEY => TripNote::class,
            Community::MORPH_KEY => Community::class,
            Post::MORPH_KEY => Post::class,
            Comment::MORPH_KEY => Comment::class,
        ]);

        Vote::observe(VoteObserver::class);
        Post::observe(PostObserver::class);
        Comment::observe(CommentObserver::class);
        Hotel::observe(HotelObserver::class);
        Room::observe(RoomObserver::class);
        City::observe(CityObserver::class);
        Region::observe(RegionObserver::class);
    }
}
