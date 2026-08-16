<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Hamza',
            'username' => User::generateUniqueUsername(),
            'email' => 'hamza@example.com',
            'password' => bcrypt('Password123'),
        ])->assignRole('admin');
    }
}
