<?php

namespace App\Policies;

use App\Enums\CommunityMemberRole;
use App\Enums\CommunityVisibility;
use App\Models\Comment;
use App\Models\Community;
use App\Models\Post;
use App\Models\User;

class CommentPolicy
{
    public function create(User $user, Post $post): bool
    {
        return $post->community->isMember($user);
    }

    public function view(User $user, Comment $comment): bool
    {
        $community = $comment->post->community;

        if ($community->visibility === CommunityVisibility::PUBLIC) {
            return true;
        }

        return $community->isMember($user);
    }

    public function update(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id || $this->canModerate($user, $comment->post->community);
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $this->update($user, $comment);
    }

    public function vote(User $user, Comment $comment): bool
    {
        return $comment->user_id !== $user->id && $comment->post->community->isMember($user);
    }

    protected function canModerate(User $user, Community $community): bool
    {
        return in_array($community->roleFor($user), [
            CommunityMemberRole::OWNER->value,
            CommunityMemberRole::ADMIN->value,
        ], true);
    }
}
