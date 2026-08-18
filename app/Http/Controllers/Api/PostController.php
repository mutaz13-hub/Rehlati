<?php

namespace App\Http\Controllers\Api;

use App\Enums\VoteType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Post\IndexPostRequest;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Requests\VoteRequest;
use App\Http\Resources\PostResource;
use App\Models\Community;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function __construct(private readonly PostService $service) {}

    public function index(IndexPostRequest $request, Community $community): JsonResponse
    {
        Gate::authorize('viewPosts', $community);

        $posts = $this->service->index($community, $request->validated());

        return $this->succeed(__('Posts fetched successfully'), [
            'posts' => PostResource::collection($posts),
            'meta' => $this->paginationMeta($posts),
        ]);
    }

    public function store(StorePostRequest $request, Community $community): JsonResponse
    {
        Gate::authorize('create', [Post::class, $community]);

        $this->service->store(
            $community,
            $request->user(),
            $request->validated(),
            $this->mediaItems($request),
        );

        return $this->succeed(__('Post created successfully'), [], 201);
    }

    public function show(Request $request, Post $post): JsonResponse
    {
        $post->loadMissing('community');
        Gate::authorize('view', $post);

        return $this->succeed(__('Post retrieved successfully'), new PostResource($this->service->show($post)));
    }

    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $post->loadMissing('community');
        Gate::authorize('update', $post);

        $this->service->update(
            $post,
            $request->validated(),
            $this->mediaItems($request),
            (bool) $request->delete_media,
        );

        return $this->succeed(__('Post updated successfully'));
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        $post->loadMissing('community');
        Gate::authorize('delete', $post);

        $this->service->destroy($post);

        return $this->succeed(__('Post deleted successfully'));
    }

    public function vote(VoteRequest $request, Post $post): JsonResponse
    {
        $post->loadMissing('community');
        Gate::authorize('vote', $post);

        $this->service->vote($post, VoteType::from($request->vote));

        return $this->succeed(__('Vote recorded'));
    }

    private function mediaItems(Request $request): array
    {
        $items = [];

        foreach ($request->file('media') ?? [] as $index => $files) {
            $items[] = [
                'type' => $request->input("media.$index.type"),
                'file' => $files['file'] ?? null,
            ];
        }

        return array_values(array_filter($items, fn (array $item) => $item['file'] instanceof UploadedFile));
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
