<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

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
        true;
    }

    public function update(User $user, Room $room): bool
    {
        true;
    }

    public function delete(User $user, Room $room): bool
    {
        true;
    }
}
