<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id;
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id;
    }
}
