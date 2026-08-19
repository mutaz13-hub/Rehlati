<?php

namespace App\Policies;

use App\Enums\CommunityMemberRole;
use App\Models\Community;
use App\Models\User;

class CommunityPolicy
{
    public function view(User $user, Community $community): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Community $community): bool
    {
        return $community->owner_id === $user->id;
    }

    public function delete(User $user, Community $community): bool
    {
        return $community->owner_id === $user->id;
    }

    public function join(User $user, Community $community): bool
    {
        return $community->owner_id !== $user->id;
    }

    public function leave(User $user, Community $community): bool
    {
        return $community->owner_id !== $user->id && $community->isMember($user);
    }

    public function manageMembers(User $user, Community $community): bool
    {
        return in_array($community->roleFor($user), [
            CommunityMemberRole::OWNER->value,
            CommunityMemberRole::ADMIN->value,
        ], true);
    }

    public function rotateLink(User $user, Community $community): bool
    {
        return $community->owner_id === $user->id;
    }

    public function viewPosts(User $user, Community $community): bool
    {
        return $community->canViewPosts($user);
    }

    public function viewMessages(User $user, Community $community): bool
    {
        return $community->isMember($user);
    }

    public function sendMessage(User $user, Community $community): bool
    {
        return $community->isMember($user);
    }
}
