<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;
use App\Services\RatingEligibilityService;
use Illuminate\Auth\Access\Response;

class RoomPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Room $room): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Room $room): bool
    {
        return true;
    }

    public function delete(User $user, Room $room): bool
    {
        return true;
    }

    public function rate(User $user, Room $room): Response
    {
        if (! app(RatingEligibilityService::class)->canRate($user, $room)) {
            return Response::deny(__('You must have a completed trip or booking to rate this item.'));
        }

        return $user->ratings()->where('rateable_type', Room::MORPH_KEY)
            ->where('rateable_id', $room->id)
            ->exists() ? Response::deny(__('You have already rated this item.'))
                                    : Response::allow();
    }
}
