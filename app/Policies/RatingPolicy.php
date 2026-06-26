<?php

namespace App\Policies;

use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RatingPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Rating $rating): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->exists();
    }

    public function update(User $user, Rating $rating): bool
    {
        return $rating->user_id === $user->id;
    }

    public function delete(User $user, Rating $rating): bool
    {
        return $rating->user_id === $user->id;
    }

    public function vote(User $user, Rating $rating): bool
    {
        return $rating->user_id !== $user->id;
    }
}
