<?php

namespace App\Policies;

use App\Models\RoomType;
use App\Models\User;

class RoomTypePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, RoomType $roomType): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        true;
    }

    public function update(User $user, RoomType $roomType): bool
    {
        true;
    }

    public function delete(User $user, RoomType $roomType): bool
    {
        true;
    }
}
