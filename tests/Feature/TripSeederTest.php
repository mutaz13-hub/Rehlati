<?php

namespace Tests\Feature;

use App\Enums\TripStatus;
use App\Models\City;
use App\Models\Hotel;
use App\Models\Trip;
use App\Models\User;
use aywan\Polyline\Polyline;
use Database\Seeders\TripSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_a_finished_trip_with_polyline_and_an_ongoing_trip(): void
    {
        $this->seedBaseData();

        $this->seed(TripSeeder::class);

        $finished = Trip::where('title', 'Damascus Heritage Walk')->firstOrFail();
        $this->assertSame(TripStatus::FINISHED, $finished->status);
        $this->assertNotNull($finished->route_polyline);
        $this->assertSame(4, $finished->locations()->count());
        $this->assertSame(2, $finished->notes()->count());
        $this->assertSame(4, $finished->destinations()->count());

        $decoded = Polyline::decode($finished->route_polyline);
        $this->assertSame([33.5127, 36.2915], $decoded[0]);
        $this->assertSame([33.5103, 36.3003], $decoded[1]);
        $this->assertSame([33.5116, 36.3069], $decoded[2]);
        $this->assertSame([33.5062, 36.3128], $decoded[3]);

        $ongoing = Trip::where('title', 'Damascus Weekend')->firstOrFail();
        $this->assertSame(TripStatus::ACTIVE, $ongoing->status);
        $this->assertNull($ongoing->route_polyline);
        $this->assertSame(4, $ongoing->locations()->count());
        $this->assertSame(2, $ongoing->notes()->count());
        $this->assertSame(4, $ongoing->destinations()->count());
    }

    public function test_trips_belong_to_the_first_user_and_have_members(): void
    {
        $this->seedBaseData();

        $this->seed(TripSeeder::class);

        $owner = User::query()->orderBy('id')->first();

        $finished = Trip::where('title', 'Damascus Heritage Walk')->with('memberPivots')->firstOrFail();
        $this->assertSame($owner->id, $finished->owner_id);
        $this->assertSame(2, $finished->memberPivots->count());
        $this->assertSame(
            ['editor', 'viewer'],
            $finished->memberPivots->map(fn ($member) => $member->role->value)->all()
        );

        $ongoing = Trip::where('title', 'Damascus Weekend')->with('memberPivots')->firstOrFail();
        $this->assertSame($owner->id, $ongoing->owner_id);
        $this->assertSame(
            ['editor', 'viewer'],
            $ongoing->memberPivots->map(fn ($member) => $member->role->value)->all()
        );
    }

    public function test_seeded_locations_and_notes_use_real_damascus_coordinates(): void
    {
        $this->seedBaseData();

        $this->seed(TripSeeder::class);

        $ongoing = Trip::where('title', 'Damascus Weekend')->with('locations', 'notes')->firstOrFail();

        $this->assertSame(
            [[33.506, 36.28], [33.508, 36.3], [33.524, 36.306], [33.545, 36.292]],
            $ongoing->locations->map(fn ($location) => [(float) $location->latitude, (float) $location->longitude])->values()->all()
        );

        $this->assertSame(
            [[33.506, 36.28], [33.545, 36.292]],
            $ongoing->notes->map(fn ($note) => [(float) $note->latitude, (float) $note->longitude])->values()->all()
        );
    }

    public function test_finished_trip_destinations_are_visited_and_ongoing_are_not(): void
    {
        $this->seedBaseData();

        $this->seed(TripSeeder::class);

        $finished = Trip::where('title', 'Damascus Heritage Walk')
            ->with(['cities.destinations'])
            ->firstOrFail();

        $this->assertSame(
            [true, true, true, true],
            $finished->cities->first()->destinations->map(fn ($destination) => $destination->visited_at !== null)->all()
        );

        $ongoing = Trip::where('title', 'Damascus Weekend')
            ->with(['cities.destinations'])
            ->firstOrFail();

        $this->assertSame(
            [true, false, false, false],
            $ongoing->cities->first()->destinations->map(fn ($destination) => $destination->visited_at !== null)->all()
        );
    }

    private function seedBaseData(): void
    {
        $damascus = City::create(['name_en' => 'Damascus', 'name_ar' => 'دمشق']);

        foreach (['Old City' => 'المدينة القديمة', 'Kafr Souseh' => 'كفر سوسة', 'Baramkeh' => 'البرامكة'] as $name => $nameAr) {
            $damascus->regions()->create(['name_en' => $name, 'name_ar' => $nameAr]);
        }

        Hotel::create([
            'name_en' => 'Al-Sham Palace Hotel',
            'name_ar' => 'فندق قصر الشام',
            'city_id' => $damascus->id,
            'stars' => 5,
        ]);

        User::factory()->count(5)->create();
    }
}
