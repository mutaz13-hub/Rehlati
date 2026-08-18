<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\IndexCommunityRequest;
use App\Http\Requests\Community\MembersIndexRequest;
use App\Http\Requests\Community\StoreCommunityRequest;
use App\Http\Requests\Community\UpdateCommunityMemberRoleRequest;
use App\Http\Requests\Community\UpdateCommunityRequest;
use App\Http\Resources\CommunityMemberResource;
use App\Http\Resources\CommunityResource;
use App\Models\Community;
use App\Models\CommunityMember;
use App\Services\CommunityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommunityController extends Controller
{
    public function __construct(private readonly CommunityService $service) {}

    public function index(IndexCommunityRequest $request): JsonResponse
    {
        $communities = $this->service->index($request->user(), $request->validated());

        return $this->succeed(__('Communities fetched successfully'), [
            'communities' => CommunityResource::collection($communities),
            'meta' => $this->paginationMeta($communities),
        ]);
    }

    public function store(StoreCommunityRequest $request): JsonResponse
    {
        $this->service->store($request->user(), $request->validated(), $request->file('cover'));

        return $this->succeed(__('Community created successfully'), [], 201);
    }

    public function show(Community $community): JsonResponse
    {
        Gate::authorize('view', $community);

        return $this->succeed(__('Community retrieved successfully'), new CommunityResource($this->service->show($community)));
    }

    public function update(UpdateCommunityRequest $request, Community $community): JsonResponse
    {
        Gate::authorize('update', $community);

        $this->service->update($community, $request->validated(), $request->file('cover'), (bool) $request->delete_cover);

        return $this->succeed(__('Community updated successfully'));
    }

    public function destroy(Community $community): JsonResponse
    {
        Gate::authorize('delete', $community);

        $this->service->destroy($community);

        return $this->succeed(__('Community deleted successfully'));
    }

    public function join(Request $request, Community $community): JsonResponse
    {
        Gate::authorize('join', $community);

        $this->service->join($community, $request->user());

        return $this->succeed(__('You have joined the community successfully'), [], 201);
    }

    public function leave(Request $request, Community $community): JsonResponse
    {
        Gate::authorize('leave', $community);

        $this->service->leave($community, $request->user());

        return $this->succeed(__('You have left the community successfully'));
    }

    public function members(MembersIndexRequest $request, Community $community): JsonResponse
    {
        Gate::authorize('viewPosts', $community);

        $members = $this->service->members($community, $request->status);

        return $this->succeed(__('Community members fetched successfully'), [
            'members' => CommunityMemberResource::collection($members),
            'meta' => $this->paginationMeta($members),
        ]);
    }

    public function updateMemberRole(UpdateCommunityMemberRoleRequest $request, Community $community, CommunityMember $communityMember): JsonResponse
    {
        Gate::authorize('manageMembers', $community);

        $this->service->updateMemberRole($community, $communityMember, $request->role);

        return $this->succeed(__('Member role updated successfully'));
    }

    public function approveMember(Request $request, Community $community, CommunityMember $communityMember): JsonResponse
    {
        Gate::authorize('manageMembers', $community);

        $this->service->approveMember($community, $communityMember);

        return $this->succeed(__('Join request approved successfully'));
    }

    public function rejectMember(Request $request, Community $community, CommunityMember $communityMember): JsonResponse
    {
        Gate::authorize('manageMembers', $community);

        $this->service->rejectMember($community, $communityMember);

        return $this->succeed(__('Join request rejected successfully'));
    }

    public function removeMember(Request $request, Community $community, CommunityMember $communityMember): JsonResponse
    {
        Gate::authorize('manageMembers', $community);

        $this->service->removeMember($community, $communityMember);

        return $this->succeed(__('Member removed successfully'));
    }

    public function rotateLink(Request $request, Community $community): JsonResponse
    {
        Gate::authorize('rotateLink', $community);

        $this->service->rotateUuid($community);

        return $this->succeed(__('Community link regenerated successfully'));
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
