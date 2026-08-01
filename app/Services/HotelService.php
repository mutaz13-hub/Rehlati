<?php

namespace App\Services;

use App\Models\Hotel;
use Illuminate\Support\Facades\DB;

class HotelService
{
    public function create(array $data): void
    {
        DB::transaction(function () use ($data): void {
            $hotel = Hotel::create($data);

            foreach ($data['pics'] ?? [] as $picture) {
                app(ImageUploadService::class)->addUploaded($hotel, $picture, 'hotel_pictures');
            }
        });
    }

    public function update(Hotel $hotel, array $data): void
    {
        DB::transaction(function () use ($hotel, $data): void {
            $hotel->update($data);

            foreach ($data['pics'] ?? [] as $picture) {
                app(ImageUploadService::class)->addUploaded($hotel, $picture, 'hotel_pictures');
            }
        });
    }

    public function delete(Hotel $hotel): void
    {
        $hotel->delete();
    }
}
