<?php

namespace App\Policies;

use App\Models\TouristGuide;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TouristGuidePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, TouristGuide $guide): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, TouristGuide $guide): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, TouristGuide $guide): bool
    {
        return $user->hasRole('admin');
    }

    public function rate(User $user, TouristGuide $guide): Response
    {
        return $user->ratings()->where('rateable_type', TouristGuide::MORPH_KEY)
            ->where('rateable_id', $guide->id)
            ->exists() ? Response::deny(__('You have already rated this item.'))
                                    : Response::allow();
    }
}
