<?php

namespace App\Http\Controllers\Mutual;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\StoreTagRequest;
use App\Http\Requests\Tag\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::paginate(20);

        return TagResource::collection($tags);
    }

    public function show(Tag $tag)
    {
        return new TagResource($tag);
    }

    public function store(StoreTagRequest $request, TagService $tagService): JsonResponse
    {
        $tagService->create($request->validated());

        return $this->succeed(__('Tag created'), 201);
    }

    public function update(UpdateTagRequest $request, Tag $tag, TagService $tagService): JsonResponse
    {
        $tagService->update($tag, $request->validated());

        return $this->succeed(__('Tag updated'));
    }

    public function destroy(Tag $tag, TagService $tagService): JsonResponse
    {
        $this->authorize('delete', $tag);

        $tagService->delete($tag);

        return response()->json([], 204);
    }
}
