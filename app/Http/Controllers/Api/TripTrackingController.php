<?php

namespace App\Http\Controllers\Api;

use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trip\AddMemberRequest;
use App\Http\Requests\Trip\StoreLocationPingRequest;
use App\Http\Requests\Trip\StoreTripCityRequest;
use App\Http\Requests\Trip\StoreTripDestinationRequest;
use App\Http\Requests\Trip\StoreTripNoteRequest;
use App\Http\Requests\Trip\StoreTripRequest;
use App\Http\Requests\Trip\UpdateTripCityRequest;
use App\Http\Requests\Trip\UpdateTripDestinationRequest;
use App\Http\Requests\Trip\UpdateTripMemberRoleRequest;
use App\Http\Requests\Trip\UpdateTripRequest;
use App\Http\Requests\Trip\UpdateTripStatusRequest;
use App\Http\Resources\TripListResource;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use App\Models\TripCity;
use App\Models\TripDestination;
use App\Models\TripMember;
use App\Models\User;
use App\Services\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TripTrackingController extends Controller
{
    public function __construct(private readonly TripService $tripService) {}

    /**
     * List trips where the authenticated user is owner or member.
     */
    public function index(Request $request): JsonResponse
    {
        $trips = $this->tripService->index($request->user());

        return $this->succeed(__('Trips fetched successfully'), [
            'trips' => TripListResource::collection($trips),
            'meta' => $this->paginationMeta($trips),
        ]);
    }

    /**
     * Create a new trip as the authenticated user (owner).
     */
    public function store(StoreTripRequest $request): JsonResponse
    {
        $this->tripService->create($request->user(), $request->validated());

        return $this->succeed(
            __('Trip created successfully'),
            [],
            201,
        );
    }

    /**
     * Show a trip the authenticated user can access.
     */
    public function show(Request $request, Trip $trip): JsonResponse
    {
        Gate::forUser($request->user())->authorize('view', $trip);

        return $this->succeed(__('Trip retrieved successfully'), new TripResource($this->tripService->show($trip)));
    }

    /**
     * Owner only: edit trip details while the trip is still being prepared.
     */
    public function update(UpdateTripRequest $request, Trip $trip): JsonResponse
    {
        Gate::forUser($request->user())->authorize('update', $trip);

        $this->tripService->update($trip, $request->validated());

        return $this->succeed(__('Trip updated successfully'));
    }

    /**
     * Owner (or editor when finishing): move the trip to the next lifecycle status.
     */
    public function updateStatus(UpdateTripStatusRequest $request, Trip $trip): JsonResponse
    {
        $target = TripStatus::from($request->validated('status'));

        Gate::forUser($request->user())->authorize('changeStatus', [$trip, $target]);

        $this->tripService->transitionStatus($trip, $target);

        return $this->succeed(__('Trip status updated successfully'));
    }

    /**
     * Public deep-linking endpoint. Anyone holding the uuid may view the
     * planned itinerary, archived polyline and memories (role: viewer).
     */
    public function showByUuid(string $uuid): JsonResponse
    {
        return $this->succeed(__('Trip retrieved successfully'), new TripResource($this->tripService->findByUuid($uuid)));
    }

    /**
     * Owner only: delete the trip and all of its attached data.
     */
    public function destroy(Request $request, Trip $trip): JsonResponse
    {
        Gate::forUser($request->user())->authorize('destroy', $trip);

        $this->tripService->destroy($trip);

        return $this->succeed(__('Trip deleted successfully'));
    }

    /**
     * Owner only: add one or more planned cities to the itinerary.
     */
    public function storePlannedCity(StoreTripCityRequest $request, Trip $trip): JsonResponse
    {
        Gate::forUser($request->user())->authorize('updateDestinations', $trip);

        $tripCities = $this->tripService->addCities($trip, $request->validated());

        return $this->succeed(
            __('Cities added to trip successfully'),
            [],
            201,
        );
    }

    /**
     * Owner only: replace the planned cities of the itinerary.
     */
    public function updatePlannedCity(UpdateTripCityRequest $request, Trip $trip): JsonResponse
    {
        Gate::forUser($request->user())->authorize('updateDestinations', $trip);

        $this->tripService->updateCities($trip, $request->validated());

        return $this->succeed(
            __('Cities updated successfully')
        );
    }

    /**
     * Owner only: remove a planned city (and its destinations) from the itinerary.
     */
    public function removePlannedCity(Request $request, Trip $trip, TripCity $tripCity): JsonResponse
    {
        Gate::forUser($request->user())->authorize('updateDestinations', $trip);

        $this->tripService->removeCity($trip, $tripCity);

        return $this->succeed(__('City removed from trip successfully'));
    }

    /**
     * Owner only: add one or more hotels/regions to a planned trip city.
     */
    public function storePlannedDestination(StoreTripDestinationRequest $request, Trip $trip): JsonResponse
    {
        Gate::forUser($request->user())->authorize('updateDestinations', $trip);

        $this->tripService->addDestinations($trip, $request->validated());

        return $this->succeed(
            __('Destination added successfully'),
            [],
            201,
        );
    }

    /**
     * Owner only: replace the planned destinations of a trip city.
     */
    public function updatePlannedDestination(UpdateTripDestinationRequest $request, Trip $trip): JsonResponse
    {
        Gate::forUser($request->user())->authorize('updateDestinations', $trip);

        $this->tripService->updateDestinations($trip, $request->validated());

        return $this->succeed(
            __('Destinations updated successfully')
        );
    }

    /**
     * Owner only: remove a planned destination from a trip city.
     */
    public function removePlannedDestination(Request $request, Trip $trip, TripDestination $tripDestination): JsonResponse
    {
        Gate::forUser($request->user())->authorize('updateDestinations', $trip);

        $this->tripService->removeDestination($trip, $tripDestination);

        return $this->succeed(__('Destination removed from trip successfully'));
    }

    /**
     * Owner or editor: push a live GPS ping into the spatial telemetry table.
     */
    public function storeLocationPing(StoreLocationPingRequest $request, Trip $trip): JsonResponse
    {
        Gate::forUser($request->user())->authorize('pushLocation', $trip);

        $this->tripService->pushLocation(
            $trip,
            (float) $request->latitude,
            (float) $request->longitude,
        );

        return $this->succeed(__('Location ping recorded successfully'));
    }

    /**
     * Owner or editor: log a memory/check-in with an optional snapshot.
     */
    public function storeTripNote(StoreTripNoteRequest $request, Trip $trip): JsonResponse
    {
        Gate::forUser($request->user())->authorize('createNote', $trip);

        $this->tripService->addNote(
            $trip,
            $request->validated(),
            array_values($request->file('pictures') ?? []),
        );

        return $this->succeed(
            __('Trip note created successfully'),
            [],
            201,
        );
    }

    /**
     * Owner only: invite a collaborator by username. The invitee must accept
     * before they gain access, and joins as a viewer by default.
     */
    public function inviteMember(AddMemberRequest $request, Trip $trip): JsonResponse
    {
        Gate::forUser($request->user())->authorize('manageMembers', $trip);

        $invitedUser = User::where('username', $request->user_name)->firstOrFail();
        $this->tripService->inviteMember($trip, $invitedUser, $request->user());

        return $this->succeed(
            __('Invitation sent successfully'),
            [],
            201,
        );
    }

    /**
     * Owner only: change a member's role between viewer and editor.
     */
    public function updateMemberRole(UpdateTripMemberRoleRequest $request, Trip $trip, User $user): JsonResponse
    {
        Gate::forUser($request->user())->authorize('manageMembers', $trip);

        $this->tripService->updateMemberRole($trip, $user, $request->role);

        return $this->succeed(__('Member role updated successfully'));
    }

    /**
     * The invited user only: accept the trip invitation.
     */
    public function acceptInvitation(Request $request, Trip $trip, User $user): JsonResponse
    {
        Gate::forUser($request->user())->authorize('respondToInvitation', [$trip, $user]);

        $this->tripService->respondToInvitation($trip, $user, true);

        return $this->succeed(__('Invitation accepted successfully'));
    }

    /**
     * The invited user only: reject the trip invitation.
     */
    public function rejectInvitation(Request $request, Trip $trip, User $user): JsonResponse
    {
        Gate::forUser($request->user())->authorize('respondToInvitation', [$trip, $user]);

        $this->tripService->respondToInvitation($trip, $user, false);

        return $this->succeed(__('Invitation rejected successfully'));
    }

    /**
     * Owner only: invalidate the current share link by assigning a new uuid.
     */
    public function rotateLink(Request $request, Trip $trip): JsonResponse
    {
        Gate::forUser($request->user())->authorize('regenerateLink', $trip);

        $trip = $this->tripService->regenerateUuid($trip);

        return $this->succeed(__('Trip link regenerated successfully'));
    }

    /**
     * Owner only: remove a collaborator.
     */
    public function removeMember(Request $request, Trip $trip, TripMember $tripMember): JsonResponse
    {
        Gate::forUser($request->user())->authorize('manageMembers', $trip);

        $this->tripService->removeMember($trip, $tripMember);

        return $this->succeed(__('Member removed successfully'));
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
