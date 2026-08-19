<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreCommunityMessageRequest;
use App\Http\Resources\CommunityMessageResource;
use App\Models\Community;
use App\Models\CommunityMessage;
use App\Services\CommunityMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommunityMessageController extends Controller
{
    public function __construct(private readonly CommunityMessageService $service) {}

    public function index(Request $request, Community $community): JsonResponse
    {
        Gate::authorize('viewMessages', $community);

        $messages = $this->service->index($community);

        return $this->succeed(__('Messages fetched successfully'), [
            'messages' => CommunityMessageResource::collection($messages),
            'meta' => $this->paginationMeta($messages),
        ]);
    }

    public function store(StoreCommunityMessageRequest $request, Community $community): JsonResponse
    {
        Gate::authorize('sendMessage', $community);

        $message = $this->service->store($community, $request->user(), $request->validated());

        return $this->succeed(__('Message sent successfully'), [
            'message' => new CommunityMessageResource($message->load('user:id,name')),
        ], 201);
    }

    public function destroy(Request $request, Community $community, CommunityMessage $message): JsonResponse
    {
        $message->loadMissing('community');
        Gate::authorize('delete', $message);

        $this->service->destroy($community, $message);

        return $this->succeed(__('Message deleted successfully'));
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
