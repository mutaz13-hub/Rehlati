<?php

namespace App\Observers;

use App\Models\City;

class CityObserver
{
    public function deleted(City $city): void
    {
        $city->description()->delete();
        $city->location()->delete();
        $city->reviews()->delete();
        $city->tags()->detach();
        $city->clearMediaCollection('city_pictures');
        $city->regions()->each(function ($region) {
            $region->delete();
        });
        $city->hotels()->each(function ($hotel) {
            $hotel->delete();
        });
    }
}
