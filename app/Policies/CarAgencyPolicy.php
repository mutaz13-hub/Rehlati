<?php

namespace App\Policies;

use App\Models\CarAgency;
use App\Models\User;
use App\Services\RatingEligibilityService;
use Illuminate\Auth\Access\Response;

class CarAgencyPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, CarAgency $carAgency): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, CarAgency $carAgency): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, CarAgency $carAgency): bool
    {
        return $user->hasRole('admin');
    }

    public function rate(User $user, CarAgency $carAgency): Response
    {
        if (! app(RatingEligibilityService::class)->canRate($user, $carAgency)) {
            return Response::deny(__('You must have a completed trip or booking to rate this item.'));
        }

        return $user->ratings()->where('rateable_type', CarAgency::MORPH_KEY)
            ->where('rateable_id', $carAgency->id)
            ->exists() ? Response::deny(__('You have already rated this item.'))
                                    : Response::allow();
    }
}
