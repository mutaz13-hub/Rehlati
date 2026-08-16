<?php

namespace App\Observers;

use App\Models\Post;

class PostObserver
{
    public function deleted(Post $post): void
    {
        $post->comments()->get()->each->delete();
        $post->votes()->delete();
        $post->voteTotals()->delete();
        $post->clearMediaCollection('post_pictures');
        $post->clearMediaCollection('post_videos');
        $post->clearMediaCollection('post_audio');
    }
}
