<?php

namespace App\Jobs;

use App\Enums\TripStatus;
use App\Models\Trip;
use App\Services\SpatialService;
use aywan\Polyline\Polyline;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessTripPolylineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $tripId) {}

    public function handle(SpatialService $spatial): void
    {
        $trip = Trip::find($this->tripId);

        if (! $trip || $trip->status !== TripStatus::FINISHED) {
            return;
        }

        $points = $trip->locations()
            ->orderBy('created_at')
            ->select($spatial->pointSelect('trip_locations'))
            ->get()
            ->map(fn ($location): array => [
                (float) $location->latitude,
                (float) $location->longitude,
            ])
            ->values()
            ->all();

        DB::transaction(function () use ($trip, $points): void {
            $trip->update([
                'route_polyline' => $points === [] ? null : Polyline::encode($points),
            ]);

            // Raw telemetry is pruned once it has been archived into the polyline.
            $trip->locations()->delete();
        });
    }
}
