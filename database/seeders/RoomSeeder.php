<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

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
                $childMultiplier = 0.5;

                $priceTiers = [
                    ['price_type' => 'base_price', 'nationality_category' => 'syrian',    'amount' => round($data['base_price_usd'] * $syrianMultiplier, 2)],
                    ['price_type' => 'base_price', 'nationality_category' => 'expat',     'amount' => round($data['base_price_usd'] * $expatMultiplier, 2)],
                    ['price_type' => 'base_price', 'nationality_category' => 'foreigner', 'amount' => round($data['base_price_usd'] * $foreignerMultiplier, 2)],
                    ['price_type' => 'child_price', 'nationality_category' => 'syrian',    'amount' => round($data['base_price_usd'] * $syrianMultiplier * $childMultiplier, 2)],
                    ['price_type' => 'child_price', 'nationality_category' => 'expat',     'amount' => round($data['base_price_usd'] * $expatMultiplier * $childMultiplier, 2)],
                    ['price_type' => 'child_price', 'nationality_category' => 'foreigner', 'amount' => round($data['base_price_usd'] * $foreignerMultiplier * $childMultiplier, 2)],
                ];

                $existingKeys = [];
                foreach ($room->prices()->get() as $p) {
                    $existingKey = "{$p->price_type}|{$p->nationality_category}|".($p->season_id ?? 'NULL');
                    $existingKeys[$existingKey] = true;
                }

                foreach ($priceTiers as $tier) {
                    $key = "{$tier['price_type']}|{$tier['nationality_category']}|NULL";
                    if (! isset($existingKeys[$key])) {
                        $room->prices()->create([
                            'price_type' => $tier['price_type'],
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

    /**
     * Seed fake room media locally using GD.
     */
    private function seedFakeMedia(Room $room, int $roomIndex): void
    {
        // Clear existing media to avoid duplicate records
        $room->clearMediaCollection('room_pictures');

        // Ensure the temporary storage folder exists
        $tempDir = storage_path('app/temp_images');
        if (! File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        for ($imageIndex = 0; $imageIndex < 4; $imageIndex++) {
            try {
                // 1. Create a unique temporary local file path
                $tempPath = $tempDir.'/fake_room_'.uniqid().'.jpg';
                $this->createLocalDummyImage($tempPath);

                // 2. Pass local path to Spatie's native addMedia method
                $media = $room->addMedia($tempPath)
                    ->toMediaCollection('room_pictures');

                // 3. Keep thumbnail flag logic for the first 2 images
                if ($imageIndex < 2) {
                    $media->setCustomProperty('is_thumbnail', true);
                    $media->save();
                }
            } catch (\Exception $e) {
                logger()->error('Failed seeding room media: '.$e->getMessage());

                continue;
            }
        }
    }

    /**
     * Helper method to dynamically generate an image file offline.
     */
    protected function createLocalDummyImage(string $path): void
    {
        $image = imagecreatetruecolor(800, 600);

        // Random background color for visual distinction between room items
        $bgColor = imagecolorallocate($image, rand(40, 160), rand(40, 160), rand(40, 160));
        imagefill($image, 0, 0, $bgColor);

        imagejpeg($image, $path);
        imagedestroy($image);
    }
}
