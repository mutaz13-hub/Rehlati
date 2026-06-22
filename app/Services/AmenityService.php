<?php

namespace App\Services;

use App\Models\Amenity;

class AmenityService
{
    public function create(array $data): Amenity
    {
        return Amenity::create($data);
    }

    public function update(Amenity $amenity, array $data): Amenity
    {
        $amenity->update($data);

        return $amenity;
    }

    public function delete(Amenity $amenity): void
    {
        $amenity->delete();
    }
}
