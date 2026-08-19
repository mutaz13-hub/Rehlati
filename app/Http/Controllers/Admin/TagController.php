<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tag\StoreTagRequest;
use App\Http\Requests\Admin\Tag\UpdateTagRequest;
use App\Http\Resources\Admin\AdminTagResource;
use App\Models\Tag;
use App\Services\Admin\AdminTagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class TagController extends Controller
{
    public function __construct(public AdminTagService $tag_service) {}

    public function index()
    {
        $tags = Tag::paginate(20);

        return AdminTagResource::collection($tags);
    }

    public function show(Tag $tag)
    {
        return new AdminTagResource($tag);
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        $this->tag_service->create($request->validated());

        return $this->succeed(__('Tag created'), 201);
    }

    public function update(UpdateTagRequest $request, Tag $tag): JsonResponse
    {
        $this->tag_service->update($tag, $request->validated());

        return $this->succeed(__('Tag updated'));
    }

    public function destroy(Tag $tag): JsonResponse
    {
        Gate::authorize('delete', $tag);

        $this->tag_service->delete($tag);

        return response()->json([], 204);
    }
}
