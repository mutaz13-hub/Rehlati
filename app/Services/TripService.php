<?php

namespace App\Services;

use App\Enums\TripInvitationStatus;
use App\Enums\TripMemberRole;
use App\Enums\TripStatus;
use App\Jobs\ProcessTripPolylineJob;
use App\Models\Hotel;
use App\Models\Region;
use App\Models\Trip;
use App\Models\TripCity;
use App\Models\TripDestination;
use App\Models\TripMember;
use App\Models\TripNote;
use App\Models\User;
use App\Notifications\TripInvitationNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TripService
{
    private const VISIT_RADIUS_METERS = 150;

    public function __construct(
        private readonly SpatialService $spatial,
        private readonly ImageUploadService $imageUploadService,
    ) {}

    public function index(User $user): LengthAwarePaginator
    {
        return Trip::query()
            ->where(fn ($query) => $query
                ->where('owner_id', $user->id)
                ->orWhereHas('memberPivots', fn ($query) => $query->where('user_id', $user->id)->where('status', 'approved')))
            ->latest()
            ->paginate(10);
    }

    public function create(User $user, array $data): void
    {
        Trip::create([
            'title' => $data['title'],
            'start_date' => $data['start_date'] ?? null,
            'owner_id' => $user->id,
            'status' => TripStatus::PREPARING,
        ]);
    }

    public function update(Trip $trip, array $data): Trip
    {
        $this->assertPreparing($trip);

        $trip->update(array_filter([
            'title' => $data['title'] ?? null,
            'start_date' => $data['start_date'] ?? null,
        ], fn ($value) => $value !== null));

        return $trip;
    }

    public function show(Trip $trip): Trip
    {
        return $this->loadFullTrip($trip);
    }

    public function findByUuid(string $uuid): Trip
    {
        $trip = Trip::where('uuid', $uuid)->firstOrFail();

        return $this->loadFullTrip($trip);
    }

    public function destroy(Trip $trip): void
    {
        DB::transaction(function () use ($trip): void {
            $trip->notes->each->clearMediaCollection('trip_note_pictures');
            $trip->notes()->delete();
            $trip->locations()->delete();
            $trip->cities()->delete();
            $trip->memberPivots()->delete();
            $trip->guideRequests()->delete();
            $trip->delete();
        });
    }

    public function transitionStatus(Trip $trip, TripStatus $target): void
    {
        if (! in_array($target, $trip->status->allowedTransitions(), true)) {
            throw ValidationException::withMessages([
                'status' => __('The trip cannot be moved from :from to :to', [
                    'from' => $trip->status->label(),
                    'to' => $target->label(),
                ]),
            ]);
        }

        if ($target === TripStatus::FINISHED) {
            $this->finishTrip($trip);
        } else {
            $trip->update(['status' => $target]);
        }
    }

    public function addCities(Trip $trip, array $data): Collection
    {
        $this->assertPreparing($trip);

        $created = collect();

        DB::transaction(function () use ($trip, $data, $created): void {
            $order = ($trip->cities()->max('order') ?? 0) + 1;

            foreach ($data['cities'] as $city) {
                $created->push($trip->cities()->updateOrCreate(
                    ['trip_id' => $trip->id, 'city_id' => $city['city_id']],
                    ['order' => $city['order'] ?? $order++],
                ));
            }
        });

        return $this->loadTripCities($created);
    }

    public function updateCities(Trip $trip, array $data): void
    {
        $this->assertPreparing($trip);

        DB::transaction(function () use ($trip, $data): void {
            $cityIds = collect($data['cities'])->pluck('city_id');

            $trip->cities()->whereNotIn('city_id', $cityIds)->delete();

            foreach ($data['cities'] as $city) {
                $trip->cities()->updateOrCreate([
                    'city_id' => $city['city_id'],
                ], [
                    'order' => $city['order'] ?? null,
                ]);
            }
        });
    }

    public function removeCity(Trip $trip, TripCity $tripCity): void
    {
        $this->assertPreparing($trip);
        $this->assertBelongsToTrip($trip, $tripCity->trip_id);

        $tripCity->delete();
    }

    public function addDestinations(Trip $trip, array $data): void
    {
        $this->assertPreparing($trip);

        $tripCity = TripCity::where('trip_id', $trip->id)->findOrFail($data['trip_city_id']);

        DB::transaction(function () use ($tripCity, $data): void {
            $order = ($tripCity->destinations()->max('order') ?? 0) + 1;

            foreach ($data['destinations'] as $destination) {
                $destinable = $this->resolveDestinable($destination['type'], $destination['id'], $tripCity);

                $this->assertDestinationNotDuplicated($tripCity, $destinable);

                $tripCity->destinations()->create([
                    'destinable_type' => $destinable->getMorphClass(),
                    'destinable_id' => $destinable->id,
                    'order' => $destination['order'] ?? $order++,
                ]);
            }
        });
    }

    public function updateDestinations(Trip $trip, array $data): void
    {
        $this->assertPreparing($trip);

        $tripCity = TripCity::where('trip_id', $trip->id)->findOrFail($data['trip_city_id']);

        DB::transaction(function () use ($tripCity, $data): void {
            $keep = [];

            foreach ($data['destinations'] as $destination) {
                $destinable = $this->resolveDestinable($destination['type'], $destination['id'], $tripCity);

                $keep[] = [
                    'destinable_type' => $destinable->getMorphClass(),
                    'destinable_id' => $destinable->id,
                ];
            }

            $tripCity->destinations()
                ->whereNot(function ($query) use ($keep): void {
                    foreach ($keep as $pair) {
                        $query->orWhere(fn ($query) => $query
                            ->where('destinable_type', $pair['destinable_type'])
                            ->where('destinable_id', $pair['destinable_id']));
                    }
                })
                ->delete();

            $order = ($tripCity->destinations()->max('order') ?? 0) + 1;

            foreach ($data['destinations'] as $destination) {
                $destinable = $this->resolveDestinable($destination['type'], $destination['id'], $tripCity);

                $tripCity->destinations()->updateOrCreate([
                    'destinable_type' => $destinable->getMorphClass(),
                    'destinable_id' => $destinable->id,
                ], [
                    'order' => $destination['order'] ?? $order++,
                ]);
            }
        });
    }

    public function removeDestination(Trip $trip, TripDestination $destination): void
    {
        $this->assertPreparing($trip);
        $this->assertBelongsToTrip($trip, $destination->tripCity->trip_id);

        $destination->delete();
    }

    public function loadTripCity(TripCity $tripCity): TripCity
    {
        return $tripCity->load([
            'city',
            'destinations' => fn ($query) => $query->orderBy('order')->with('destinable.location'),
        ]);
    }

    public function loadTripCities(iterable $tripCities): Collection
    {
        $ids = collect($tripCities)->pluck('id');

        return TripCity::query()
            ->whereIn('id', $ids)
            ->with([
                'city',
                'destinations' => fn ($query) => $query->orderBy('order')->with('destinable.location'),
            ])
            ->orderBy('order')
            ->get();
    }

    public function loadDestinations(iterable $destinations): Collection
    {
        $ids = collect($destinations)->pluck('id');

        return TripDestination::query()
            ->whereIn('id', $ids)
            ->with('destinable.location')
            ->orderBy('order')
            ->get();
    }

    public function pushLocation(Trip $trip, float $latitude, float $longitude): void
    {
        $this->assertActive($trip);

        if ($this->locationAlreadyRecorded($trip, $latitude, $longitude)) {
            throw ValidationException::withMessages([
                'location' => __('This location has already been recorded for the trip'),
            ]);
        }

        $trip->locations()->create(array_merge(
            ['trip_id' => $trip->id],
            $this->spatial->pointValue($latitude, $longitude),
        ));

        $this->markVisitedDestinations($trip, $latitude, $longitude);
    }

    public function addNote(Trip $trip, array $data, array $pictures = []): TripNote
    {
        $this->assertActive($trip);

        $note = $trip->notes()->create(array_merge(
            ['trip_id' => $trip->id],
            $this->spatial->pointValue((float) $data['latitude'], (float) $data['longitude']),
            ['description' => $data['description'] ?? null],
        ));

        foreach ($pictures as $picture) {
            $this->imageUploadService->addUploaded($note, $picture, 'trip_note_pictures');
        }

        return $this->loadNote($note);
    }

    public function loadNote(TripNote $note): TripNote
    {
        return TripNote::query()
            ->selectRaw($this->spatial->pointSelectRaw('trip_notes'))
            ->with('media')
            ->findOrFail($note->id);
    }

    public function inviteMember(Trip $trip, User $invitedUser, User $inviter): TripMember
    {
        // $this->assertPreparing($trip);

        if ($trip->owner_id === $invitedUser->id) {
            throw ValidationException::withMessages([
                'user_name' => __('The trip owner cannot be invited to their own trip'),
            ]);
        }

        if ($trip->memberPivots()->where('user_id', $invitedUser->id)->exists()) {
            throw ValidationException::withMessages([
                'user_name' => __('This user has already been invited to the trip'),
            ]);
        }

        $member = $trip->memberPivots()->create([
            'user_id' => $invitedUser->id,
            'role' => TripMemberRole::VIEWER->value,
            'status' => TripInvitationStatus::PENDING->value,
        ]);

        $invitedUser->notify(new TripInvitationNotification($trip, $inviter));

        return $member;
    }

    public function updateMemberRole(Trip $trip, User $memberUser, string $role): TripMember
    {
        $this->assertPreparing($trip);

        $member = $trip->memberPivots()->where('user_id', $memberUser->id)->firstOrFail();

        $member->update(['role' => $role]);

        return $member;
    }

    public function respondToInvitation(Trip $trip, User $memberUser, bool $accept): void
    {
        $member = $trip->memberPivots()
            ->where('user_id', $memberUser->id)
            ->where('status', TripInvitationStatus::PENDING->value)
            ->first();

        if (! $member) {
            throw ValidationException::withMessages([
                'user_name' => __('No pending invitation was found for this user'),
            ]);
        }

        $member->update([
            'status' => $accept ? TripInvitationStatus::APPROVED->value : TripInvitationStatus::REJECTED->value,
            'responded_at' => now(),
        ]);
    }

    public function regenerateUuid(Trip $trip): Trip
    {
        $trip->update(['uuid' => (string) Str::uuid()]);

        return $trip;
    }

    public function removeMember(Trip $trip, TripMember $member): void
    {
        // $this->assertPreparing($trip);

        if ($member->trip_id !== $trip->id) {
            abort(404);
        }

        if ($trip->owner_id === $member->user_id) {
            throw ValidationException::withMessages([
                'user_id' => __('The trip owner cannot be removed'),
            ]);
        }

        $member->delete();
    }

    private function markVisitedDestinations(Trip $trip, float $latitude, float $longitude): void
    {
        $unvisited = $trip->destinations()
            ->whereNull('visited_at')
            ->with('destinable.location')
            ->get();

        if ($unvisited->isEmpty()) {
            return;
        }

        foreach ($unvisited as $destination) {
            $location = $destination->destinable?->location;

            if ($location === null) {
                continue;
            }

            if ($this->distanceInMeters($latitude, $longitude, (float) $location->latitude, (float) $location->longitude) <= self::VISIT_RADIUS_METERS) {
                $destination->update(['visited_at' => now()]);
            }
        }

        if ($trip->destinations()->whereNull('visited_at')->doesntExist()) {
            $this->finishTrip($trip);
        }
    }

    private function finishTrip(Trip $trip): void
    {
        $trip->update(['status' => TripStatus::FINISHED]);

        ProcessTripPolylineJob::dispatch($trip->id);
    }

    private function resolveDestinable(string $type, int $id, TripCity $tripCity): Hotel|Region
    {
        $model = $type === 'hotel' ? Hotel::class : Region::class;
        $destinable = $model::findOrFail($id);

        if ($destinable->city_id !== $tripCity->city_id) {
            throw ValidationException::withMessages([
                'trip_city_id' => __('The destination does not belong to the selected trip city'),
            ]);
        }

        return $destinable;
    }

    private function assertDestinationNotDuplicated(TripCity $tripCity, Hotel|Region $destinable): void
    {
        $exists = $tripCity->destinations()
            ->where('destinable_type', $destinable->getMorphClass())
            ->where('destinable_id', $destinable->id)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'destinations' => __('This destination has already been added to the city'),
            ]);
        }
    }

    private function locationAlreadyRecorded(Trip $trip, float $latitude, float $longitude): bool
    {
        $query = $trip->locations()->getQuery();

        if ($this->spatial->supportsSpatial()) {
            $query->whereRaw(
                'ST_X(coordinates) = ? AND ST_Y(coordinates) = ?',
                [$longitude, $latitude]
            );
        } else {
            $query->where('latitude', $latitude)->where('longitude', $longitude);
        }

        return $query->exists();
    }

    private function loadFullTrip(Trip $trip): Trip
    {
        return $trip->load([
            'cities' => fn ($query) => $query->orderBy('order')->with([
                'city',
                'destinations' => fn ($query) => $query->orderBy('order')->with('destinable.location'),
            ]),
            'memberPivots.user:id,name',
            'owner:id,name,email',
            'guideRequests.touristGuide',
            'notes' => function ($query) {
                $query->selectRaw($this->spatial->pointSelectRaw('trip_notes'))
                    ->with('media');
            },
        ]);
    }

    private function assertPreparing(Trip $trip): void
    {
        if (! $trip->isPreparing()) {
            throw ValidationException::withMessages([
                'trip' => __('The itinerary can only be changed while the trip is being prepared'),
            ]);
        }
    }

    private function assertBelongsToTrip(Trip $trip, int $tripId): void
    {
        if ($trip->id !== $tripId) {
            abort(404);
        }
    }

    private function assertActive(Trip $trip): void
    {
        if (! $trip->isActive()) {
            throw ValidationException::withMessages([
                'trip' => __('This trip is no longer active'),
            ]);
        }
    }

    private function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
