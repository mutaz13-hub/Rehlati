<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Hotel;
use App\Models\Rating;
use App\Models\User;
use App\Enums\VoteType;
use Illuminate\Database\Seeder;

class RatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $city = City::first();
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->info('No users found in the database!');
            return;
        }

        if ($city) {
            $this->seedRatings($city, City::MORPH_KEY, $users);
        }

        Hotel::query()->eachById(function (Hotel $hotel) use ($users): void {
            $this->seedRatings($hotel, Hotel::MORPH_KEY, $users);
        });
    }

    private function seedRatings(object $rateable, string $rateableType, $users): void
    {
        $users->shuffle()->take(min(10, $users->count()))->each(function (User $user) use ($rateable, $rateableType, $users): void {
            $rating = Rating::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'rateable_type' => $rateableType,
                    'rateable_id' => $rateable->id,
                ],
                [
                    'rate' => fake()->numberBetween(1, 5),
                    'type' => 'text',
                    'body' => fake()->paragraph(),
                ]
            );

            $users->where('id', '!=', $user->id)
                ->shuffle()
                ->take(fake()->numberBetween(0, min(5, $users->count() - 1)))
                ->each(fn (User $voter) => $rating->votes()->firstOrCreate(
                    ['user_id' => $voter->id],
                    ['vote' => fake()->randomElement(VoteType::cases())]
                ));
        });
    }
}
