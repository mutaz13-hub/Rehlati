<?php

namespace App\Policies;

use App\Models\Amenity;
use App\Models\User;

class AmenityPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Amenity $amenity): bool
    {
        return true;
    }

    public function create(User $user): bool {}

    public function update(User $user, Amenity $amenity): bool {}

    public function delete(User $user, Amenity $amenity): bool {}
}
