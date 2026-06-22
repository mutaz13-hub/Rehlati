<?php

namespace App\Services;

use App\Models\Hotel;

class HotelService
{
    public function create(array $data): void
    {
         Hotel::create($data);
    }

    public function update(Hotel $hotel, array $data): void
    {
        $hotel->update($data);

    }

    public function delete(Hotel $hotel): void
    {
        $hotel->delete();
    }
}
