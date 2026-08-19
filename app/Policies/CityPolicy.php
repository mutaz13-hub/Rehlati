<?php

namespace App\Policies;

use App\Models\City;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CityPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, City $city): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        if ($user->hasRole('admin')) {
            if (City::count('id') >= 14) {
                return Response::deny(__('You have reached the maximum number of cities you can create.'));
            }

            return Response::allow();
        }

        return Response::deny();

    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, City $city): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, City $city): bool
    {
        return auth()->user()->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, City $city): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, City $city): bool
    {
        return false;
    }

    public function rate(User $user, City $city): Response
    {
        return $user->ratings()->where('rateable_type', City::MORPH_KEY)
            ->where('rateable_id', $city->id)
            ->exists() ? Response::deny(__('You have already rated this item.'))
                                    : Response::allow();
    }
}
