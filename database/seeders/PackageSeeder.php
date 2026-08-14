<?php

namespace Database\Seeders;

use App\Models\CarAgency;
use App\Models\City;
use App\Models\Hotel;
use App\Models\Package;
use App\Models\Region;
use App\Services\ImageUploadService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Spatie\MediaLibrary\HasMedia;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CarAgencySeeder::class);

        $cities = City::whereIn('name_en', [
            'Damascus',
            'Aleppo',
            'Homs',
            'Latakia',
            'Tartus',
            'As-Suwayda',
        ])->get()->keyBy('name_en');

        $hotels = Hotel::whereIn('name_en', [
            'Al-Sham Palace Hotel',
            'Aleppo Grand Hotel',
            'Homs View Hotel',
            'Latakia Beach Resort',
        ])->get()->keyBy('name_en');

        $carAgencies = CarAgency::all();

        $packages = [
            [
                'name_en' => 'Damascus City Break',
                'name_ar' => 'استراحة دمشق',
                'start_date' => now()->addDays(10)->format('Y-m-d'),
                'end_date' => now()->addDays(13)->format('Y-m-d'),
                'duration_days' => 3,
                'price' => 150000.00,
                'currency' => 'SYP',
                'status' => 'active',
                'description_en' => 'A short stay in Damascus with guided tours of the Old City and local markets.',
                'description_ar' => 'إقامة قصيرة في دمشق مع جولات موجهة في المدينة القديمة والأسواق المحلية.',
                'city_names' => ['Damascus'],
                'hotel_names' => ['Al-Sham Palace Hotel'],
                'with_car_agency' => true,
            ],
            [
                'name_en' => 'Aleppo Citadel Adventure',
                'name_ar' => 'مغامرة قلعة حلب',
                'start_date' => now()->addDays(14)->format('Y-m-d'),
                'end_date' => now()->addDays(18)->format('Y-m-d'),
                'duration_days' => 4,
                'price' => 170000.00,
                'currency' => 'SYP',
                'status' => 'active',
                'description_en' => 'Explore the historic Citadel of Aleppo and enjoy local cuisine on this four-day trip.',
                'description_ar' => 'اكتشف قلعة حلب التاريخية واستمتع بالمأكولات المحلية في هذه الرحلة لمدة أربعة أيام.',
                'city_names' => ['Aleppo'],
                'hotel_names' => ['Aleppo Grand Hotel'],
                'with_car_agency' => true,
            ],
            [
                'name_en' => 'Homs Heritage Tour',
                'name_ar' => 'جولة تراث حمص',
                'start_date' => now()->addDays(18)->format('Y-m-d'),
                'end_date' => now()->addDays(21)->format('Y-m-d'),
                'duration_days' => 3,
                'price' => 130000.00,
                'currency' => 'SYP',
                'status' => 'active',
                'description_en' => 'A heritage tour of Homs with museum visits and traditional dining experiences.',
                'description_ar' => 'جولة تراثية في حمص مع زيارات للمتاحف وتجارب طعام تقليدية.',
                'city_names' => ['Homs'],
                'hotel_names' => ['Homs View Hotel'],
            ],
            [
                'name_en' => 'Latakia Beach Escape',
                'name_ar' => 'هروب شاطئ اللاذقية',
                'start_date' => now()->addDays(20)->format('Y-m-d'),
                'end_date' => now()->addDays(24)->format('Y-m-d'),
                'duration_days' => 4,
                'price' => 190000.00,
                'currency' => 'SYP',
                'status' => 'active',
                'description_en' => 'Relax on the Mediterranean coast with beachside stays and seaside excursions.',
                'description_ar' => 'استرخ على الساحل السوري مع إقامة على البحر وجولات بحرية.',
                'city_names' => ['Latakia'],
                'hotel_names' => ['Latakia Beach Resort'],
                'with_car_agency' => true,
            ],
            [
                'name_en' => 'Tartus Coastal Cruise',
                'name_ar' => 'رحلة ساحلية طرطوس',
                'start_date' => now()->addDays(22)->format('Y-m-d'),
                'end_date' => now()->addDays(26)->format('Y-m-d'),
                'duration_days' => 4,
                'price' => 180000.00,
                'currency' => 'SYP',
                'status' => 'active',
                'description_en' => 'A coastal cruise around Tartus with historic port visits and seaside relaxation.',
                'description_ar' => 'رحلة ساحلية حول طرطوس مع زيارات للموانئ التاريخية واستجمام على الشاطئ.',
                'city_names' => ['Tartus'],
                'hotel_names' => [],
            ],
            [
                'name_en' => 'As-Suwayda Mountain Retreat',
                'name_ar' => 'منتجع الجبل السويداء',
                'start_date' => now()->addDays(25)->format('Y-m-d'),
                'end_date' => now()->addDays(29)->format('Y-m-d'),
                'duration_days' => 4,
                'price' => 160000.00,
                'currency' => 'SYP',
                'status' => 'active',
                'description_en' => 'A mountain retreat in As-Suwayda with scenic views and quiet countryside stays.',
                'description_ar' => 'منتجع جبلية في السويداء مع مناظر خلابة وإقامة هادئة في الريف.',
                'city_names' => ['As-Suwayda'],
                'hotel_names' => [],
            ],
        ];

        foreach ($packages as $packageData) {
            $package = Package::updateOrCreate(
                ['name_en' => $packageData['name_en']],
                [
                    'name_ar' => $packageData['name_ar'],
                    'start_date' => $packageData['start_date'],
                    'end_date' => $packageData['end_date'],
                    'duration_days' => $packageData['duration_days'],
                    'price' => $packageData['price'],
                    'currency' => $packageData['currency'],
                    'status' => $packageData['status'],
                ]
            );

            $package->description()->updateOrCreate([], [
                'description_en' => $packageData['description_en'],
                'description_ar' => $packageData['description_ar'],
            ]);

            $package->cities()->sync(
                $cities->whereIn('name_en', $packageData['city_names'])->pluck('id')->all()
            );

            $package->hotels()->sync(
                $hotels->whereIn('name_en', $packageData['hotel_names'])->pluck('id')->all()
            );

            $cityIds = $cities->whereIn('name_en', $packageData['city_names'])->pluck('id')->all();

            $package->regions()->sync(
                Region::whereIn('city_id', $cityIds)->pluck('id')->all()
            );

            if (! empty($packageData['with_car_agency']) && $carAgencies->isNotEmpty()) {
                $package->carAgencies()->sync($carAgencies->pluck('id')->all());
            }

            $this->seedFakeMedia($package, 'package_pictures', 2);
        }
    }

    /**
     * Seed fake media locally with a safe environment fallback method.
     */
    protected function seedFakeMedia(HasMedia $model, string $collectionName, int $count): void
    {
        // Clear old media objects
        $model->clearMediaCollection($collectionName);

        // Prepare temporary image folder paths
        $tempDir = storage_path('app/temp_images');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        for ($i = 0; $i < $count; $i++) {
            try {
                // 1. Path assignment
                $tempPath = $tempDir . '/fake_pkg_' . uniqid() . '.jpg';
                $this->createLocalDummyImage($tempPath);

                // 2. Integration into Spatie native media system
                $media = $model->addMedia($tempPath)
                    ->toMediaCollection($collectionName);

                // 3. Mark primary element index as thumbnail container
                if ($i === 0) {
                    $media->setCustomProperty('is_thumbnail', true);
                    $media->save();
                }
            } catch (\Exception $e) {
                logger()->error("Failed seeding package media: " . $e->getMessage());
                continue;
            }
        }
    }

    /**
     * Generate true JPEG images locally with a text-mock file format fallback.
     */
    protected function createLocalDummyImage(string $path): void
    {
        if (function_exists('imagecreatetruecolor')) {
            $image = imagecreatetruecolor(800, 600);
            
            // Build random contrast spectrum colors
            $bgColor = imagecolorallocate($image, rand(80, 190), rand(80, 190), rand(80, 190));
            imagefill($image, 0, 0, $bgColor);
            
            imagejpeg($image, $path);
            imagedestroy($image);
        } else {
            // Environment fallback structure for machines missing the GD drivers
            File::put($path, 'Offline environment placeholder data stream string asset.');
        }
    }
}
