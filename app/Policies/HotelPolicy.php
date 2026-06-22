<?php

namespace App\Policies;

use App\Models\Hotel;
use App\Models\User;

class HotelPolicy
{
    /**
     * Determine whether the user can view any hotels.
     */
    public function viewAny(?User $user): bool
    {
        // Everyone (guests and users) can view hotels
        return true;
    }

    /**
     * Determine whether the user can view the hotel.
     */
    public function view(?User $user, Hotel $hotel): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create hotels.
     */
    public function create(User $user): bool
    {
        true;
    }

    /**
     * Determine whether the user can update the hotel.
     */
    public function update(User $user, Hotel $hotel): bool
    {
        true;
    }

    /**
     * Determine whether the user can delete the hotel.
     */
    public function delete(User $user, Hotel $hotel): bool
    {
        true;
    }
}
