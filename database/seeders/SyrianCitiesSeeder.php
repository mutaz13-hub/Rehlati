<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SyrianCitiesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            'Damascus' => [
                'name_ar' => 'دمشق',
                'description_en' => 'Damascus is the capital of Syria and one of the oldest continuously inhabited cities in the world.',
                'description_ar' => 'دمشق هي عاصمة سوريا وواحدة من أقدم المدن المأهولة في العالم.',
                'regions' => [
                    ['name_en' => 'Old City', 'name_ar' => 'المدينة القديمة'],
                    ['name_en' => 'Kafr Souseh', 'name_ar' => 'كفر سوسة'],
                    ['name_en' => 'Baramkeh', 'name_ar' => 'البرامكة'],
                ],
            ],
            'Damascus Rural' => [
                'name_ar' => 'ريف دمشق',
                'description_en' => 'Rural Damascus governorate surrounding the capital.',
                'description_ar' => 'محافظة ريف دمشق المحيطة بالعاصمة.',
                'regions' => [
                    ['name_en' => 'Douma', 'name_ar' => 'دوما'],
                    ['name_en' => 'Zabadani', 'name_ar' => 'الزبداني'],
                ],
            ],
            'Aleppo' => [
                'name_ar' => 'حلب',
                'description_en' => 'Aleppo is a major city in northern Syria with a long history as a trading center.',
                'description_ar' => 'حلب مدينة رئيسية في شمال سوريا وتتمتع بتاريخ طويل كمركز تجاري.',
                'regions' => [
                    ['name_en' => 'Salah al-Din', 'name_ar' => 'صلاح الدين'],
                    ['name_en' => 'Al-Jalloum', 'name_ar' => 'الجلوم'],
                ],
            ],
            'Homs' => [
                'name_ar' => 'حمص',
                'description_en' => 'Homs is a central Syrian city and an important agricultural and industrial center.',
                'description_ar' => 'حمص مدينة في وسط سوريا ومركز زراعي وصناعي مهم.',
                'regions' => [
                    ['name_en' => 'Al-Waer', 'name_ar' => 'الوعر'],
                    ['name_en' => 'Bab Hud', 'name_ar' => 'باب هود'],
                ],
            ],
            'Hama' => [
                'name_ar' => 'حماة',
                'description_en' => 'Hama is known for its historic waterwheels and agricultural lands.',
                'description_ar' => 'حماة معروفة بنواويسها التاريخية وأراضيها الزراعية.',
                'regions' => [
                    ['name_en' => 'Al-Mahatta', 'name_ar' => 'المحطة'],
                    ['name_en' => 'Al-Sultaniyah', 'name_ar' => 'السلطانية'],
                ],
            ],
            'Latakia' => [
                'name_ar' => 'اللاذقية',
                'description_en' => 'Latakia is a major port city on the Mediterranean coast of Syria.',
                'description_ar' => 'اللاذقية مدينة ساحلية رئيسية وميناء على البحر المتوسط.',
                'regions' => [
                    ['name_en' => 'Al-Ramel', 'name_ar' => 'الرمل'],
                    ['name_en' => 'Slinfah', 'name_ar' => 'صلنفة'],
                ],
            ],
            'Tartus' => [
                'name_ar' => 'طرطوس',
                'description_en' => 'Tartus is a coastal city with historical ports and seaside attractions.',
                'description_ar' => 'طرطوس مدينة ساحلية تتميز بموانئها التاريخية ومعالمها البحرية.',
                'regions' => [
                    ['name_en' => 'Al-Basitah', 'name_ar' => 'الباسيطة'],
                    ['name_en' => 'Al-Sawda', 'name_ar' => 'السودة'],
                ],
            ],
            'Deir ez-Zor' => [
                'name_ar' => 'دير الزور',
                'description_en' => 'Deir ez-Zor lies along the Euphrates and is an agricultural hub in eastern Syria.',
                'description_ar' => 'دير الزور تقع على نهر الفرات وهي مركز زراعي في شرق سوريا.',
                'regions' => [
                    ['name_en' => 'Al-Jazeera', 'name_ar' => 'الجزيرة'],
                    ['name_en' => 'Al-Busayrah', 'name_ar' => 'البصيرة'],
                ],
            ],
            'Raqqa' => [
                'name_ar' => 'الرقة',
                'description_en' => 'Raqqa is a city on the northeast of the country with historic agricultural importance.',
                'description_ar' => 'الرقة مدينة في شمال شرق البلاد وتتمتع بأهمية زراعية تاريخية.',
                'regions' => [
                    ['name_en' => 'Al-Thawrah', 'name_ar' => 'الثورة'],
                    ['name_en' => 'Tal Abyad', 'name_ar' => 'تل أبيض'],
                ],
            ],
            'Idlib' => [
                'name_ar' => 'إدلب',
                'description_en' => 'Idlib is a city in northwest Syria known for its orchards and historical sites.',
                'description_ar' => 'إدلب مدينة في شمال غرب سوريا معروفة ببساتينها ومواقعها التاريخية.',
                'regions' => [
                    ['name_en' => 'Maarrat al-Numan', 'name_ar' => 'معرة النعمان'],
                    ['name_en' => 'Saraqib', 'name_ar' => 'سراقب'],
                ],
            ],
            'Daraa' => [
                'name_ar' => 'درعا',
                'description_en' => 'Daraa is a southern city near the Jordanian border, known for its agriculture.',
                'description_ar' => 'درعا مدينة جنوبية قرب الحدود مع الأردن ومعروفة بالزراعة.',
                'regions' => [
                    ['name_en' => 'Nawa', 'name_ar' => 'ناصرة'],
                    ['name_en' => 'Izraa', 'name_ar' => 'إزرع'],
                ],
            ],
            'Quneitra' => [
                'name_ar' => 'القنيطرة',
                'description_en' => 'Quneitra is located in the southwest of Syria near the Golan Heights.',
                'description_ar' => 'القنيطرة تقع في جنوب غرب سوريا قرب هضبة الجولان.',
                'regions' => [
                    ['name_en' => 'Quneitra Countryside', 'name_ar' => 'ريف القنيطرة'],
                ],
            ],
            'Al-Hasakah' => [
                'name_ar' => 'الحسكة',
                'description_en' => 'Al-Hasakah is a northeastern governorate with diverse communities and agriculture.',
                'description_ar' => 'الحسكة محافظة في شمال شرق سوريا ذات تنوع سكاني وزراعي.',
                'regions' => [
                    ['name_en' => 'Qamishli', 'name_ar' => 'القامشلي'],
                    ['name_en' => 'Ras al-Ayn', 'name_ar' => 'رأس العين'],
                ],
            ],
            'As-Suwayda' => [
                'name_ar' => 'السويداء',
                'description_en' => 'As-Suwayda is a highland city known for its Druze community and vineyards.',
                'description_ar' => 'السويداء مدينة جبلية معروفة بمجتمعها الدرزي وكروم العنب.',
                'regions' => [
                    ['name_en' => 'Salkhad', 'name_ar' => 'صلخد'],
                    ['name_en' => 'Shahba', 'name_ar' => 'شبعا'],
                ],
            ],
        ];

        // Get all tags first
        $tags = Tag::all();

        foreach ($cities as $name => $data) {
            $city = City::updateOrCreate(
                ['name_en' => $name],
                ['name_en' => $name, 'name_ar' => $data['name_ar']]
            );

            // Add or update bilingual description
            $city->description()->updateOrCreate(
                [],
                [
                    'description_en' => $data['description_en'],
                    'description_ar' => $data['description_ar'],
                ]
            );

            // Attach 3 random tags to city
            $randomTags = $tags->random(3);
            $city->tags()->syncWithoutDetaching($randomTags);

            // Add regions for the city
            foreach ($data['regions'] as $region) {
                $regionModel = $city->regions()->updateOrCreate(
                    ['name_en' => $region['name_en']],
                    ['name_ar' => $region['name_ar']]
                );

                // Attach 3 random tags to region
                $regionRandomTags = $tags->random(3);
                $regionModel->tags()->syncWithoutDetaching($regionRandomTags);
            }
        }
    }
}
