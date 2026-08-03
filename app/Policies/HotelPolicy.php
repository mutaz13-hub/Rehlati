<?php

namespace App\Policies;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Auth\Access\Response;

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
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the hotel.
     */
    public function update(User $user, Hotel $hotel): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the hotel.
     */
    public function delete(User $user, Hotel $hotel): bool
    {
       return $user->hasRole('admin');
    }

    public function rate(User $user, Hotel $hotel): Response
    {
        return ($user->ratings()->where('rateable_type', Hotel::MORPH_KEY)
                         ->where('rateable_id', $hotel->id)
                         ->exists() ? Response::deny(__('You have already rated this item.'))
                                    : Response::allow());
    }
}
