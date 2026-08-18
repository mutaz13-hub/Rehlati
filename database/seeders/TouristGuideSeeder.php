<?php

namespace Database\Seeders;

use App\Models\TouristGuide;
use Illuminate\Database\Seeder;

class TouristGuideSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guides = [
            [
                'name' => 'Sami Al-Khatib',
                'email' => 'sami@example.com',
                'phone' => '+963 944 000 001',
                'availability' => ['saturday', 'sunday', 'monday', 'tuesday'],
                'price_per_hour' => 15,
                'is_active' => true,
            ],
            [
                'name' => 'Lina Haddad',
                'email' => 'lina@example.com',
                'phone' => '+963 944 000 002',
                'availability' => ['wednesday', 'thursday', 'friday', 'saturday'],
                'price_per_hour' => 12,
                'is_active' => true,
            ],
            [
                'name' => 'Omar Nassif',
                'email' => 'omar@example.com',
                'phone' => '+963 944 000 003',
                'availability' => ['sunday', 'monday', 'thursday', 'friday'],
                'price_per_hour' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Rania Suleiman',
                'email' => 'rania@example.com',
                'phone' => '+963 944 000 004',
                'availability' => ['tuesday', 'wednesday', 'friday', 'saturday'],
                'price_per_hour' => 18,
                'is_active' => false,
            ],
        ];

        foreach ($guides as $guide) {
            TouristGuide::updateOrCreate(
                ['email' => $guide['email']],
                $guide,
            );
        }
    }
}
