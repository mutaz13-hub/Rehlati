<?php

namespace App\Services;

use App\Enums\GuideRequestStatus;
use App\Models\GuideRequest;
use App\Models\TouristGuide;
use App\Models\Trip;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuideRequestService
{
    public function indexForAdmin(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = GuideRequest::query()
            ->with(['trip.owner:id,name,email', 'touristGuide' => $this->guideWithRatings()])
            ->latest();

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function show(GuideRequest $guideRequest): GuideRequest
    {
        return $guideRequest->load(['trip.owner:id,name,email', 'touristGuide' => $this->guideWithRatings()]);
    }

    public function indexForTrip(Trip $trip): Collection
    {
        return $trip->guideRequests()
            ->with(['touristGuide' => $this->guideWithRatings()])
            ->latest()
            ->get();
    }

    public function store(Trip $trip, array $data): GuideRequest
    {
        if (! $trip->isPreparing()) {
            throw ValidationException::withMessages([
                'trip' => __('The itinerary can only be changed while the trip is being prepared'),
            ]);
        }

        $guide = TouristGuide::where('id', $data['tourist_guide_id'])
            ->where('is_active', true)
            ->first();

        if (! $guide) {
            throw ValidationException::withMessages([
                'tourist_guide_id' => __('The selected tourist guide is not available'),
            ]);
        }

        $pending = $trip->guideRequests()
            ->where('tourist_guide_id', $guide->id)
            ->where('status', GuideRequestStatus::PENDING->value)
            ->exists();

        if ($pending) {
            throw ValidationException::withMessages([
                'tourist_guide_id' => __('A pending booking request already exists for this tourist guide'),
            ]);
        }

        return DB::transaction(function () use ($trip, $guide, $data) {
            return $trip->guideRequests()->create([
                'tourist_guide_id' => $guide->id,
                'status' => GuideRequestStatus::PENDING->value,
                'note' => $data['note'] ?? null,
            ]);
        });
    }

    public function cancel(Trip $trip, GuideRequest $guideRequest): void
    {
        if ($guideRequest->trip_id !== $trip->id) {
            abort(404);
        }

        if ($guideRequest->status !== GuideRequestStatus::PENDING) {
            throw ValidationException::withMessages([
                'guide_request' => __('Only pending booking requests can be cancelled'),
            ]);
        }

        $guideRequest->delete();
    }

    public function respond(GuideRequest $guideRequest, GuideRequestStatus $status): void
    {
        if ($guideRequest->status !== GuideRequestStatus::PENDING) {
            throw ValidationException::withMessages([
                'guide_request' => __('This booking request has already been handled'),
            ]);
        }

        $guideRequest->update([
            'status' => $status->value,
            'responded_at' => now(),
        ]);
    }

    private function guideWithRatings(): \Closure
    {
        return fn ($query) => $query->withCount('reviews')->withAvg('reviews', 'rate');
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['status']) && in_array($filters['status'], GuideRequestStatus::values(), true)) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['tourist_guide_id'])) {
            $query->where('tourist_guide_id', $filters['tourist_guide_id']);
        }

        if (! empty($filters['trip_id'])) {
            $query->where('trip_id', $filters['trip_id']);
        }

        if (! empty($filters['q'])) {
            $query->where(function (Builder $q) use ($filters) {
                $q->whereHas('touristGuide', fn (Builder $gq) => $gq
                    ->where('name', 'like', "%{$filters['q']}%")
                    ->orWhere('email', 'like', "%{$filters['q']}%"))
                    ->orWhereHas('trip', fn (Builder $tq) => $tq->where('title', 'like', "%{$filters['q']}%"));
            });
        }
    }
}
