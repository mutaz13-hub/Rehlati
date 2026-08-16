<?php

namespace App\Http\Controllers\Api;

use App\Enums\VoteType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\IndexCommentRequest;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Http\Requests\VoteRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function __construct(private readonly CommentService $service) {}

    public function index(IndexCommentRequest $request, Post $post): JsonResponse
    {
        $post->loadMissing('community');
        Gate::authorize('view', $post);

        $comments = $this->service->index($post, $request->validated());

        return $this->succeed(__('Comments fetched successfully'), [
            'comments' => CommentResource::collection($comments),
            'meta' => $this->paginationMeta($comments),
        ]);
    }

    public function store(StoreCommentRequest $request, Post $post): JsonResponse
    {
        $post->loadMissing('community');
        Gate::authorize('create', [Comment::class, $post]);

        $this->service->store(
            $post,
            $request->user(),
            $request->validated(),
            array_values($request->file('pictures') ?? []),
            $request->file('video'),
            $request->file('audio'),
        );

        return $this->succeed(__('Comment created successfully'), [], 201);
    }

    public function update(UpdateCommentRequest $request, Comment $comment): JsonResponse
    {
        $comment->loadMissing('post.community');
        Gate::authorize('update', $comment);

        $this->service->update(
            $comment,
            $request->validated(),
            array_values($request->file('pictures') ?? []),
            $request->file('video'),
            $request->file('audio'),
            (bool) $request->delete_pictures,
        );

        return $this->succeed(__('Comment updated successfully'));
    }

    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        $comment->loadMissing('post.community');
        Gate::authorize('delete', $comment);

        $this->service->destroy($comment);

        return $this->succeed(__('Comment deleted successfully'));
    }

    public function vote(VoteRequest $request, Comment $comment): JsonResponse
    {
        $comment->loadMissing('post.community');
        Gate::authorize('vote', $comment);

        $this->service->vote($comment, VoteType::from($request->vote));

        return $this->succeed(__('Vote recorded'));
    }

    private function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
