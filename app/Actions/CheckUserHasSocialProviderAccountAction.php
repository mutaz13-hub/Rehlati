<?php

namespace App\Actions;

use App\Models\SocialProvider;
use App\Models\User;

class CheckUserHasSocialProviderAccountAction
{
    public function execute(User $user, SocialProvider $provider): bool
    {
        return $user->socialAccounts()
            ->where('social_provider_id', $provider->id)
            ->exists();
    }
}
