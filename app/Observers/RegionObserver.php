<?php

namespace App\Observers;

use App\Models\Region;

class RegionObserver
{
    public function deleting(Region $region): void
    {
        $region->description()->delete();
        $region->location()->delete();
        $region->tags()->detach();
        $region->clearMediaCollection('region_pictures');
    }
}
