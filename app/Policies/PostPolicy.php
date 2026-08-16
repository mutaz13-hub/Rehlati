<?php

namespace App\Policies;

use App\Enums\CommunityMemberRole;
use App\Enums\CommunityVisibility;
use App\Models\Community;
use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function create(User $user, Community $community): bool
    {
        return $community->isMember($user);
    }

    public function view(User $user, Post $post): bool
    {
        $community = $post->community;

        if ($community->visibility === CommunityVisibility::PUBLIC) {
            return true;
        }

        return $community->isMember($user);
    }

    public function update(User $user, Post $post): bool
    {
        return $post->user_id === $user->id || $this->canModerate($user, $post->community);
    }

    public function delete(User $user, Post $post): bool
    {
        return $this->update($user, $post);
    }

    public function vote(User $user, Post $post): bool
    {
        return $post->user_id !== $user->id && $post->community->isMember($user);
    }

    protected function canModerate(User $user, Community $community): bool
    {
        return in_array($community->roleFor($user), [
            CommunityMemberRole::OWNER->value,
            CommunityMemberRole::ADMIN->value,
        ], true);
    }
}
