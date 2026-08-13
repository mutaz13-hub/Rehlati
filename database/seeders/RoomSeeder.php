<?php

namespace Database\Seeders;

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
                    
                    'description_en' => 'A bright and spacious room with a king-size bed, a comfortable seating area, and views over the city.',
                    'description_ar' => 'غرفة مشرقة وواسعة تضم سريراً بحجم كينغ ومنطقة جلوس مريحة وإطلالة على المدينة.',
                    'base_price_usd' => 40 + ($hotel->stars * 20),
                    'total_rooms' => 12,
                    'available_rooms' => 8,
                ],
                [
                    'name_en' => 'Family Twin Suite',
                    'name_ar' => 'جناح عائلي بسريرين منفصلين',
                    
                    'description_en' => 'A welcoming family suite with two twin beds, extra living space, and everything needed for a relaxed stay.',
                    'description_ar' => 'جناح عائلي مريح يضم سريرين منفصلين ومساحة معيشة إضافية وكل ما يلزم لإقامة هادئة.',
                    'base_price_usd' => 65 + ($hotel->stars * 20),
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
                        'total_rooms' => $data['total_rooms'],
                        'available_rooms' => $data['available_rooms'],
                    ]
                );

                $room->description()->updateOrCreate([], [
                    'description_en' => $data['description_en'],
                    'description_ar' => $data['description_ar'],
                ]);

                $syrianMultiplier = 1.0;
                $expatMultiplier = 1.1;
                $foreignerMultiplier = 1.25;

                $basePriceTiers = [
                    ['nationality_category' => 'syrian',    'amount' => round($data['base_price_usd'] * $syrianMultiplier, 2)],
                    ['nationality_category' => 'expat',     'amount' => round($data['base_price_usd'] * $expatMultiplier, 2)],
                    ['nationality_category' => 'foreigner', 'amount' => round($data['base_price_usd'] * $foreignerMultiplier, 2)],
                ];

                $existingKeys = [];
                foreach ($room->prices()->where('price_type', 'base_price')->get() as $p) {
                    $existingKey = "{$p->nationality_category}|" . ($p->season_id ?? 'NULL');
                    $existingKeys[$existingKey] = true;
                }

                foreach ($basePriceTiers as $tier) {
                    $key = "{$tier['nationality_category']}|NULL";
                    if (!isset($existingKeys[$key])) {
                        $room->prices()->create([
                            'price_type' => 'base_price',
                            'nationality_category' => $tier['nationality_category'],
                            'currency' => 'USD',
                            'amount' => $tier['amount'],
                            'season_id' => null,
                        ]);
                    }
                }

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
                    "http://picsum.photos/800/600?random=room-{$room->id}-{$roomIndex}-{$imageIndex}-" . uniqid(),
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

