<?php

namespace App\Services;

use App\Enums\VoteType;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CommentService
{
    public function __construct(private readonly ImageUploadService $imageUploadService) {}

    public function index(Post $post, array $options = []): LengthAwarePaginator
    {
        $sort = $options['sort'] ?? 'top';

        $query = Comment::where('post_id', $post->id)
            ->with(['user:id,name', 'voteTotals']);

        if ($sort === 'latest') {
            $query = $query->latest();
        } else { // top
            $query = $query->leftJoin('vote_totals', function ($join) {
                $join->on('vote_totals.voteable_id', 'comments.id')
                    ->where('vote_totals.voteable_type', Comment::MORPH_KEY);
            })->select('comments.*')->groupBy('comments.id')->orderByRaw("
                SUM(CASE WHEN vote_totals.vote_type = 'up' THEN vote_totals.count ELSE 0 END) -
                SUM(CASE WHEN vote_totals.vote_type = 'down' THEN vote_totals.count ELSE 0 END) DESC
            ")->orderByDesc('comments.created_at');
        }

        return $query->paginate(10);
    }

    public function store(Post $post, User $user, array $data, array $pictures = [], ?UploadedFile $video = null, ?UploadedFile $audio = null): void
    {
        DB::transaction(function () use ($post, $user, $data, $pictures, $video, $audio) {
            $comment = Comment::create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'type' => $data['type'],
                'body' => $data['body'] ?? null,
            ]);

            foreach ($pictures as $picture) {
                $this->imageUploadService->addUploaded($comment, $picture, 'comment_pictures');
            }

            if ($video) {
                $comment->addMedia($video)->toMediaCollection('comment_videos');
            }

            if ($audio) {
                $comment->addMedia($audio)->toMediaCollection('comment_audio');
            }
        });
    }

    public function update(Comment $comment, array $data, array $pictures = [], ?UploadedFile $video = null, ?UploadedFile $audio = null, bool $deletePictures = false): void
    {
        DB::transaction(function () use ($comment, $data, $pictures, $video, $audio, $deletePictures) {
            $updateData = [];

            if (array_key_exists('body', $data)) {
                $updateData['body'] = $data['body'];
            }

            if (isset($data['type']) && $data['type'] !== $comment->type->value) {
                $comment->clearMediaCollection('comment_pictures');
                $comment->clearMediaCollection('comment_videos');
                $comment->clearMediaCollection('comment_audio');
                $updateData['type'] = $data['type'];
            }

            if (! empty($updateData)) {
                $comment->update($updateData);
            }

            if ($pictures) {
                $comment->clearMediaCollection('comment_pictures');
                foreach ($pictures as $picture) {
                    $this->imageUploadService->addUploaded($comment, $picture, 'comment_pictures');
                }
            } elseif ($deletePictures) {
                $comment->clearMediaCollection('comment_pictures');
            }

            if ($video) {
                $comment->clearMediaCollection('comment_videos');
                $comment->addMedia($video)->toMediaCollection('comment_videos');
            }

            if ($audio) {
                $comment->clearMediaCollection('comment_audio');
                $comment->addMedia($audio)->toMediaCollection('comment_audio');
            }
        });
    }

    public function destroy(Comment $comment): void
    {
        $comment->delete();
    }

    public function vote(Comment $comment, VoteType $voteType): void
    {
        DB::transaction(function () use ($comment, $voteType) {
            $userId = auth('sanctum')->id();

            $vote = $comment->votes()->where('user_id', $userId)->first();

            if ($vote) {
                $same = $vote->vote === $voteType;
                $vote->delete();
                if ($same) {
                    return;
                }
            }

            $comment->votes()->create([
                'user_id' => $userId,
                'vote' => $voteType,
            ]);
        });
    }
}
