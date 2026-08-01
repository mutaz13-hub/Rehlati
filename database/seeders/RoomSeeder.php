<?php

namespace Database\Seeders;

use App\Enums\BedType;
use App\Enums\RoomType;
use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Hotel::query()->eachById(function (Hotel $hotel): void {
            $rooms = [
                [
                    'name_en' => 'Deluxe King Room',
                    'name_ar' => 'غرفة ديلوكس بسرير كينغ',
                    'room_type' => RoomType::DELUXE,
                    'bed_type' => BedType::KING,
                    'description_en' => 'A bright and spacious room with a king-size bed, a comfortable seating area, and views over the city.',
                    'description_ar' => 'غرفة مشرقة وواسعة تضم سريراً بحجم كينغ ومنطقة جلوس مريحة وإطلالة على المدينة.',
                    'price_per_night' => 40 + ($hotel->stars * 20),
                    'total_rooms' => 12,
                    'available_rooms' => 8,
                ],
                [
                    'name_en' => 'Family Twin Suite',
                    'name_ar' => 'جناح عائلي بسريرين منفصلين',
                    'room_type' => RoomType::SUITE,
                    'bed_type' => BedType::TWIN,
                    'description_en' => 'A welcoming family suite with two twin beds, extra living space, and everything needed for a relaxed stay.',
                    'description_ar' => 'جناح عائلي مريح يضم سريرين منفصلين ومساحة معيشة إضافية وكل ما يلزم لإقامة هادئة.',
                    'price_per_night' => 65 + ($hotel->stars * 20),
                    'total_rooms' => 8,
                    'available_rooms' => 5,
                ],
            ];

            Room::query()
                ->where('hotel_id', $hotel->id)
                ->whereNotIn('name_en', array_column($rooms, 'name_en'))
                ->eachById(fn (Room $room) => $room->delete());

            foreach ($rooms as $index => $data) {
                $room = Room::updateOrCreate(
                    ['hotel_id' => $hotel->id, 'name_en' => $data['name_en']],
                    [
                        'name_ar' => $data['name_ar'],
                        'room_type' => $data['room_type'],
                        'bed_type' => $data['bed_type'],
                        'price_per_night' => $data['price_per_night'],
                        'total_rooms' => $data['total_rooms'],
                        'available_rooms' => $data['available_rooms'],
                    ]
                );

                $room->description()->updateOrCreate([], [
                    'description_en' => $data['description_en'],
                    'description_ar' => $data['description_ar'],
                ]);

                $this->seedFakeMedia($room, $index);
            }
        });
    }

    private function seedFakeMedia(Room $room, int $roomIndex): void
    {
        $room->clearMediaCollection('room_pictures');

        for ($imageIndex = 0; $imageIndex < 4; $imageIndex++) {
            try {
              $media =  app(\App\Services\ImageUploadService::class)->addFromUrl(
                    $room,
                    "https://picsum.photos/800/600?random=room-{$room->id}-{$roomIndex}-{$imageIndex}-" . uniqid(),
                    'room_pictures',
                );

                if($imageIndex < 2) {
                    $media->setCustomProperty('is_thumbnail', true);
                    $media->save();
                }
            } catch (\Exception) {
                continue;
            }
        }
    }
}
