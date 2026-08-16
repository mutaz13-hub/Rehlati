<?php

namespace App\Observers;

use App\Models\Comment;

class CommentObserver
{
    public function deleted(Comment $comment): void
    {
        $comment->votes()->delete();
        $comment->voteTotals()->delete();
        $comment->clearMediaCollection('comment_pictures');
        $comment->clearMediaCollection('comment_videos');
        $comment->clearMediaCollection('comment_audio');
    }
}
