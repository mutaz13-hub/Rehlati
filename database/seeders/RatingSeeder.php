<?php

namespace Database\Seeders;

use App\Models\City;
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
        // Get the first city from the database
        $city = City::first();
        
        if (!$city) {
            $this->command->info('No city found in the database!');
            return;
        }

        // Get all users
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->info('No users found in the database!');
            return;
        }

        // Create ~10 ratings for this city
        for ($i = 0; $i < 10; $i++) {
            // Get random user
            $user = $users->random();
            
            // Check if user already has a rating for this city to respect unique constraint
            $existingRating = Rating::where('user_id', $user->id)
                ->where('rateable_type', City::MORPH_KEY)
                ->where('rateable_id', $city->id)
                ->first();
                
            if ($existingRating) {
                continue; // Skip if already rated
            }

            // Determine rating type (text or audio)
            $type = fake()->randomElement(['text', 'audio']);
            
            $ratingData = [
                'user_id' => $user->id,
                'rateable_type' => City::MORPH_KEY,
                'rateable_id' => $city->id,
                'rate' => fake()->numberBetween(1, 5),
                'type' => $type,
                'body' => $type === 'text' ? fake()->paragraph() : null,
            ];

            $rating = Rating::create($ratingData);
            
            // Optionally add some random fake votes to ratings (optional, but nice!)
            $randomUsers = $users->random(fake()->numberBetween(0, min(5, $users->count())));
            foreach ($randomUsers as $voter) {
                if ($voter->id !== $user->id) {
                    $rating->votes()->create([
                        'user_id' => $voter->id,
                        'vote' => fake()->randomElement(VoteType::cases()),
                    ]);
                }
            }
        }
    }
}
