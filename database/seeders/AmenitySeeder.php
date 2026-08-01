<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        $amenities = [
            ['name_en' => 'Free WiFi', 'name_ar' => 'واي فاي مجاني', 'slug' => 'wifi'],
    ['name_en' => 'Swimming Pool', 'name_ar' => 'مسبح', 'slug' => 'pool'],
    ['name_en' => 'Gym', 'name_ar' => 'صالة رياضية', 'slug' => 'fitness_center'],
    ['name_en' => 'Restaurant', 'name_ar' => 'مطعم', 'slug' => 'restaurant'],
    ['name_en' => 'Café', 'name_ar' => 'مقهى', 'slug' => 'local_cafe'],
    ['name_en' => 'Free Parking', 'name_ar' => 'مواقف سيارات مجانية', 'slug' => 'local_parking'],
    ['name_en' => '24-Hour Front Desk', 'name_ar' => 'مكتب استقبال 24 ساعة', 'slug' => 'support_agent'],
    ['name_en' => 'Spa & Wellness Center', 'name_ar' => 'منتجع صحي وعافية', 'slug' => 'spa'],
    ['name_en' => 'Elevator', 'name_ar' => 'مصعد', 'slug' => 'elevator'],
    ['name_en' => 'Pet Friendly', 'name_ar' => 'يسمح باصطحاب الحيوانات الأليفة', 'slug' => 'pets'],
    ['name_en' => 'Conference Room', 'name_ar' => 'قاعة مؤتمرات', 'slug' => 'meeting_room'],
    ['name_en' => 'Business Center', 'name_ar' => 'مركز رجال الأعمال', 'slug' => 'business_center'],
    ['name_en' => 'Airport Shuttle', 'name_ar' => 'مكوك نقل المطار', 'slug' => 'airport_shuttle'],
    ['name_en' => 'Currency Exchange', 'name_ar' => 'صرف عملات', 'slug' => 'currency_exchange'],
    ['name_en' => 'Garden', 'name_ar' => 'حديقة', 'slug' => 'yard'],



    ['name_en' => 'Air Conditioning', 'name_ar' => 'تكييف الهواء', 'slug' => 'ac_unit'],
    ['name_en' => 'Room Service', 'name_ar' => 'خدمة الغرف', 'slug' => 'room_service'],
    ['name_en' => 'Breakfast Included', 'name_ar' => 'إفطار متضمن', 'slug' => 'free_breakfast'],
    ['name_en' => 'Laundry Service', 'name_ar' => 'خدمة غسيل ملابس', 'slug' => 'local_laundry_service'],
    ['name_en' => 'Family Rooms', 'name_ar' => 'غرف عائلية', 'slug' => 'family_restroom'],
    ['name_en' => 'Non-Smoking Rooms', 'name_ar' => 'غرف للغير المدخنين', 'slug' => 'smoke_free'],
    ['name_en' => 'Babysitting Services', 'name_ar' => 'خدمات جليسة أطفال', 'slug' => 'child_care'],
    ['name_en' => 'Car Rental', 'name_ar' => 'تأجير سيارات', 'slug' => 'directions_car'],
    ['name_en' => 'Beauty Services', 'name_ar' => 'خدمات تجميل', 'slug' => 'content_cut'],
    ['name_en' => 'Mini Bar', 'name_ar' => 'حافظة مشروبات في الغرفة', 'slug' => 'kitchen'],
    ['name_en' => 'Flat Screen TV', 'name_ar' => 'تلفزيون بشاشة مسطحة', 'slug' => 'tv'],
    ['name_en' => 'Private Bathroom', 'name_ar' => 'حمام خاص', 'slug' => 'shower'],
    ['name_en' => 'Wake-up Service', 'name_ar' => 'خدمة الاستيقاظ', 'slug' => 'access_alarm'],
    ['name_en' => 'Souvenir Shop', 'name_ar' => 'محل هدايا وتذكارات', 'slug' => 'card_giftcard'],
    ['name_en' => 'Terrace', 'name_ar' => 'تراس', 'slug' => 'deck']
        ];

        foreach ($amenities as $amenity) {
            Amenity::updateOrCreate(
                ['name_en' => $amenity['name_en']],
                $amenity
            );
        }
    }
}
