<?php

namespace App\Policies;

use App\Models\CommunityMessage;
use App\Models\User;

class CommunityMessagePolicy
{
    public function delete(User $user, CommunityMessage $message): bool
    {
        return $message->user_id === $user->id;
    }
}
