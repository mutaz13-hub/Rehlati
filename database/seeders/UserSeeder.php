<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 10 test users with default password 'password'
        $users = User::factory(10)->create();

        foreach ($users as $user) {
            $user->assignRole('user');
        }
    }
}
