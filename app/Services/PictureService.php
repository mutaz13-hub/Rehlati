<?php

namespace App\Services;

use App\Models\City;
use App\Models\Hotel;
use App\Models\Package;
use App\Models\Region;

class PictureService
{
    public function city(City $city)
    {
        return $city->media()->where('collection_name', 'city_pictures')->latest()->cursorPaginate(10);
    }

    public function region(Region $region)
    {
        return $region->media()->where('collection_name', 'region_pictures')->latest()->cursorPaginate(10);
    }

    public function hotel(Hotel $hotel)
    {
        return $hotel->media()->where('collection_name', 'hotel_pictures')->latest()->cursorPaginate(10);
    }

    public function package(Package $package)
    {
        return $package->media()->where('collection_name', 'package_pictures')->latest()->cursorPaginate(10);
    }
}
