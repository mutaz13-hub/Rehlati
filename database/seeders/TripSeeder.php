<?php

namespace Database\Seeders;

use App\Enums\TripInvitationStatus;
use App\Enums\TripMemberRole;
use App\Enums\TripStatus;
use App\Models\City;
use App\Models\Hotel;
use App\Models\Region;
use App\Models\Trip;
use App\Models\User;
use App\Services\SpatialService;
use aywan\Polyline\Polyline;
use Illuminate\Database\Seeder;

class TripSeeder extends Seeder
{
    private const DAMASCUS_POINTS = [
        ['name' => 'Damascus Citadel', 'latitude' => 33.5127, 'longitude' => 36.2915],
        ['name' => 'Al-Hamidiyah Souq', 'latitude' => 33.5103, 'longitude' => 36.3003],
        ['name' => 'Umayyad Mosque', 'latitude' => 33.5116, 'longitude' => 36.3069],
        ['name' => 'Bab Sharqi (Straight Street)', 'latitude' => 33.5062, 'longitude' => 36.3128],
        ['name' => 'Tishreen Park', 'latitude' => 33.5060, 'longitude' => 36.2800],
        ['name' => 'Merjeh Square', 'latitude' => 33.5080, 'longitude' => 36.3000],
        ['name' => 'Damascus Opera House', 'latitude' => 33.5240, 'longitude' => 36.3060],
        ['name' => 'Mount Qasioun', 'latitude' => 33.5450, 'longitude' => 36.2920],
    ];

    public function run(SpatialService $spatial): void
    {
        $owner = User::query()->orderBy('id')->firstOrFail();
        $members = User::query()->where('id', '!=', $owner->id)->orderBy('id')->take(4)->get();
        $damascus = City::where('name_en', 'Damascus')->firstOrFail();

        $this->seedFinishedHeritageWalk($spatial, $owner, $members, $damascus);
        $this->seedOngoingWeekendTrip($spatial, $owner, $members, $damascus);
    }

    private function seedFinishedHeritageWalk(
        SpatialService $spatial,
        User $owner,
        $members,
        City $damascus,
    ): void {
        $points = [
            self::DAMASCUS_POINTS[0], // Damascus Citadel
            self::DAMASCUS_POINTS[1], // Al-Hamidiyah Souq
            self::DAMASCUS_POINTS[2], // Umayyad Mosque
            self::DAMASCUS_POINTS[3], // Bab Sharqi
        ];

        $trip = Trip::updateOrCreate(
            ['title' => 'Damascus Heritage Walk'],
            [
                'uuid' => '00000000-0000-0000-0000-000000000001',
                'start_date' => now()->subWeek(),
                'owner_id' => $owner->id,
                'status' => TripStatus::FINISHED,
                'route_polyline' => Polyline::encode(array_map(
                    fn (array $point): array => [$point['latitude'], $point['longitude']],
                    $points,
                )),
            ],
        );

        $this->resetTrip($trip);
        $this->addTripCity($trip, $damascus);
        $this->addDestinations($trip, [
            ['model' => $this->region($damascus, 'Old City'), 'latitude' => 33.5127, 'longitude' => 36.2915, 'visited' => true],
            ['model' => $this->region($damascus, 'Baramkeh'), 'latitude' => 33.5103, 'longitude' => 36.3003, 'visited' => true],
            ['model' => Hotel::where('name_en', 'Al-Sham Palace Hotel')->firstOrFail(), 'latitude' => 33.5116, 'longitude' => 36.3069, 'visited' => true],
            ['model' => $this->region($damascus, 'Kafr Souseh'), 'latitude' => 33.5062, 'longitude' => 36.3128, 'visited' => true],
        ]);
        $this->addLocations($spatial, $trip, $points);
        $this->addNotes($spatial, $trip, [
            [
                'name' => 'Umayyad Mosque',
                'description' => 'Visited the Great Mosque in the morning — the mosaics are breathtaking.',
            ],
            [
                'name' => 'Al-Hamidiyah Souq',
                'description' => 'Bought spices and dried fruits at the Hamidiyah souq before heading back.',
            ],
        ]);

        $this->addMember($trip, $members[0], TripMemberRole::EDITOR, TripInvitationStatus::APPROVED);
        $this->addMember($trip, $members[1], TripMemberRole::VIEWER, TripInvitationStatus::APPROVED);
    }

    private function seedOngoingWeekendTrip(
        SpatialService $spatial,
        User $owner,
        $members,
        City $damascus,
    ): void {
        $points = [
            self::DAMASCUS_POINTS[4], // Tishreen Park
            self::DAMASCUS_POINTS[5], // Merjeh Square
            self::DAMASCUS_POINTS[6], // Damascus Opera House
            self::DAMASCUS_POINTS[7], // Mount Qasioun
        ];

        $trip = Trip::updateOrCreate(
            ['title' => 'Damascus Weekend'],
            [
                'uuid' => '00000000-0000-0000-0000-000000000002',
                'start_date' => now(),
                'owner_id' => $owner->id,
                'status' => TripStatus::ACTIVE,
                'route_polyline' => null,
            ],
        );

        $this->resetTrip($trip);
        $this->addTripCity($trip, $damascus);
        $this->addDestinations($trip, [
            ['model' => $this->newRegion($damascus, 'Tishreen Park', 'حديقة تشرين'), 'latitude' => 33.5060, 'longitude' => 36.2800, 'visited' => true],
            ['model' => $this->newRegion($damascus, 'Merjeh Square', 'ساحة المرجة'), 'latitude' => 33.5080, 'longitude' => 36.3000, 'visited' => false],
            ['model' => $this->newRegion($damascus, 'Damascus Opera', 'دار الأوبرا'), 'latitude' => 33.5240, 'longitude' => 36.3060, 'visited' => false],
            ['model' => $this->newRegion($damascus, 'Mount Qasioun', 'جبل قاسيون'), 'latitude' => 33.5450, 'longitude' => 36.2920, 'visited' => false],
        ]);
        $this->addLocations($spatial, $trip, $points);
        $this->addNotes($spatial, $trip, [
            [
                'name' => 'Tishreen Park',
                'description' => 'Breakfast by the park fountain, planning the next stop.',
            ],
            [
                'name' => 'Mount Qasioun',
                'description' => 'Up on Qasioun at sunset — full view of the city.',
            ],
        ]);

        $this->addMember($trip, $members[2], TripMemberRole::EDITOR, TripInvitationStatus::APPROVED);
        $this->addMember($trip, $members[3], TripMemberRole::VIEWER, TripInvitationStatus::PENDING);
    }

    private function resetTrip(Trip $trip): void
    {
        $trip->notes()->get()->each->clearMediaCollection('trip_note_pictures');
        $trip->notes()->delete();
        $trip->locations()->delete();
        $trip->cities()->delete();
        $trip->memberPivots()->delete();
    }

    private function addTripCity(Trip $trip, City $city): void
    {
        $trip->cities()->create([
            'city_id' => $city->id,
            'order' => 1,
        ]);
    }

    private function addDestinations(Trip $trip, array $destinations): void
    {
        $tripCity = $trip->cities()->firstOrFail();

        foreach ($destinations as $order => $destination) {
            $destination['model']->location()->updateOrCreate([], [
                'latitude' => $destination['latitude'],
                'longitude' => $destination['longitude'],
            ]);

            $tripCity->destinations()->updateOrCreate(
                [
                    'destinable_type' => $destination['model']->getMorphClass(),
                    'destinable_id' => $destination['model']->id,
                ],
                [
                    'order' => $order + 1,
                    'visited_at' => $destination['visited'] ? now() : null,
                ],
            );
        }
    }

    private function region(City $city, string $name): Region
    {
        return $city->regions()->where('name_en', $name)->firstOrFail();
    }

    private function newRegion(City $city, string $nameEn, string $nameAr): Region
    {
        return Region::updateOrCreate(
            ['name_en' => $nameEn],
            ['name_ar' => $nameAr, 'city_id' => $city->id],
        );
    }

    private function addLocations(SpatialService $spatial, Trip $trip, array $points): void
    {
        $order = 0;

        foreach ($points as $point) {
            $trip->locations()->create(array_merge(
                ['trip_id' => $trip->id, 'created_at' => now()->addMinutes($order++)],
                $spatial->pointValue($point['latitude'], $point['longitude']),
            ));
        }
    }

    private function addNotes(SpatialService $spatial, Trip $trip, array $notes): void
    {
        $nameToPoint = collect(self::DAMASCUS_POINTS)->keyBy('name');

        foreach ($notes as $note) {
            $point = $nameToPoint->get($note['name']);

            $trip->notes()->create(array_merge(
                ['trip_id' => $trip->id],
                $spatial->pointValue($point['latitude'], $point['longitude']),
                ['description' => $note['description']],
            ));
        }
    }

    private function addMember(Trip $trip, User $user, TripMemberRole $role, TripInvitationStatus $status): void
    {
        $trip->memberPivots()->create([
            'user_id' => $user->id,
            'role' => $role->value,
            'status' => $status->value,
            'responded_at' => $status === TripInvitationStatus::APPROVED ? now() : null,
        ]);
    }
}
