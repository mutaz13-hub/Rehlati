<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BedType;

class BedTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bedTypes = [
            ['name_en' => 'Single Bed', 'name_ar' => 'سرير مفرد', 'default_capacity' => 1],
            ['name_en' => 'Double Bed', 'name_ar' => 'سرير مزدوج', 'default_capacity' => 2],
            ['name_en' => 'Queen Bed', 'name_ar' => 'سرير كوين', 'default_capacity' => 2],
            ['name_en' => 'King Bed', 'name_ar' => 'سرير كينغ', 'default_capacity' => 2],
            ['name_en' => 'Twin Beds', 'name_ar' => 'أسرة توأم', 'default_capacity' => 2],
            ['name_en' => 'Bunk Beds', 'name_ar' => 'أسرة بطابقين', 'default_capacity' => 2],
        ];

        foreach ($bedTypes as $bedType) {
            BedType::updateOrCreate(
                ['name_en' => $bedType['name_en']],
                $bedType
            );
        }
    }
}
