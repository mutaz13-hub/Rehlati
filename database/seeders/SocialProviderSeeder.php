<?php

namespace Database\Seeders;

use App\Models\SocialProvider;
use Illuminate\Database\Seeder;

class SocialProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SocialProvider::query()->firstOrCreate(['name' => 'google']);
    }
}
