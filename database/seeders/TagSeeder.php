<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            ['name_en' => 'Historical', 'name_ar' => 'تاريخي'],
            ['name_en' => 'Cultural', 'name_ar' => 'ثقافي'],
            ['name_en' => 'Coastal', 'name_ar' => 'ساحلي'],
            ['name_en' => 'Mountainous', 'name_ar' => 'جبلي'],
            ['name_en' => 'Agricultural', 'name_ar' => 'زراعي'],
            ['name_en' => 'Religious', 'name_ar' => 'ديني'],
            ['name_en' => 'Commercial', 'name_ar' => 'تجاري'],
            ['name_en' => 'Natural Beauty', 'name_ar' => 'جمال طبيعي'],
            ['name_en' => 'Ancient Sites', 'name_ar' => 'مواقع قديمة'],
            ['name_en' => 'Traditional', 'name_ar' => 'تقليدي'],
            ['name_en' => 'Shopping', 'name_ar' => 'تسوق'],
            ['name_en' => 'Family Friendly', 'name_ar' => 'مناسب للعائلات'],
        ];

        foreach ($tags as $tag) {
            Tag::updateOrCreate(
                ['name_en' => $tag['name_en']],
                $tag
            );
        }
    }
}
