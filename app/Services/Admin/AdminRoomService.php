<?php

namespace App\Services\Admin;

use App\Models\Room;
use App\Models\Hotel;
use App\Services\PriceUserService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminRoomService
{
    public function __construct(
        private readonly PriceUserService $priceUserService
    ) {
    }

    public function create(Hotel $hotel, array $data): Room
    {
        return DB::transaction(function () use ($hotel, $data) {
            $prices = $data['prices'] ?? [];
            unset($data['prices']);

            $room = Room::create([
                'hotel_id' => $hotel->id,
                'name_en' => $data['name_en'],
                'name_ar' => $data['name_ar'],
                'room_class' => $data['room_class'],
                'room_layout' => $data['room_layout'],
                'max_adults' => $data['max_adults'],
                'max_children' => $data['max_children'],
                'max_guests' => $data['max_guests'],
                'room_type' => $data['room_type'] ?? null,
                'bed_type' => $data['bed_type'] ?? null,
                'total_rooms' => $data['total_rooms'],
                'available_rooms' => $data['available_rooms'],
            ]);

            if (isset($data['description_en']) || isset($data['description_ar'])) {
                $room->description()->create([
                    'description_en' => $data['description_en'] ?? '',
                    'description_ar' => $data['description_ar'] ?? '',
                ]);
            }

            if (isset($data['amenities']) && is_array($data['amenities'])) {
                $room->amenities()->sync($data['amenities']);
            }

            if (isset($data['beds']) && is_array($data['beds'])) {
                $room->bedTypes()->sync($this->formatBedsForSync($data['beds']));
            }

            if (!empty($prices)) {
                $room->prices()->createMany($this->formatPricesForCreate($prices));
            }

            $this->priceUserService->clearPricesCaches($room);

            return $room->load(['amenities', 'bedTypes', 'description', 'prices.season']);
        });
    }

    public function update(Room $room, array $data): Room
    {
        return DB::transaction(function () use ($room, $data) {
            $prices = $data['prices'] ?? null;
            unset($data['prices']);

            $room->update(array_filter([
                'name_en' => $data['name_en'] ?? null,
                'name_ar' => $data['name_ar'] ?? null,
                'room_class' => $data['room_class'] ?? null,
                'room_layout' => $data['room_layout'] ?? null,
                'max_adults' => $data['max_adults'] ?? null,
                'max_children' => $data['max_children'] ?? null,
                'max_guests' => $data['max_guests'] ?? null,
                'room_type' => $data['room_type'] ?? null,
                'bed_type' => $data['bed_type'] ?? null,
                'total_rooms' => $data['total_rooms'] ?? null,
                'available_rooms' => $data['available_rooms'] ?? null,
            ], fn ($value) => $value !== null));

            if (isset($data['description_en']) || isset($data['description_ar'])) {
                $room->description()->updateOrCreate(
                    ['describable_id' => $room->id, 'describable_type' => Room::MORPH_KEY],
                    [
                        'description_en' => $data['description_en'] ?? $room->description->description_en ?? '',
                        'description_ar' => $data['description_ar'] ?? $room->description->description_ar ?? '',
                    ]
                );
            }

            if (isset($data['amenities']) && is_array($data['amenities'])) {
                $room->amenities()->sync($data['amenities']);
            }

            if (isset($data['beds']) && is_array($data['beds'])) {
                $room->bedTypes()->sync($this->formatBedsForSync($data['beds']));
            }

            if ($prices !== null) {
                $room->prices()->delete();
                if (!empty($prices)) {
                    $room->prices()->createMany($this->formatPricesForCreate($prices));
                }
            }

            $this->priceUserService->clearPricesCaches($room);

            return $room->load(['amenities', 'bedTypes', 'description', 'prices.season']);
        });
    }

    public function delete(Room $room): void
    {
        DB::transaction(function () use ($room): void {
            $this->priceUserService->clearPricesCaches($room);

            $room->prices()->delete();
            $room->description()->delete();
            $room->amenities()->detach();
            $room->bedTypes()->detach();
            $room->delete();
        });
    }

    private function formatBedsForSync(array $beds): array
    {
        $syncData = [];
        foreach ($beds as $bed) {
            $bedTypeId = $bed['bed_type_id'];
            $syncData[$bedTypeId] = [
                'quantity' => $bed['quantity'],
                'assigned_capacity' => $bed['assigned_capacity'],
            ];
        }
        return $syncData;
    }

    private function formatPricesForCreate(array $prices): array
    {
        return array_map(function ($price) {
            return [
                'price_type' => $price['price_type'],
                'nationality_category' => $price['nationality_category'],
                'currency' => strtoupper($price['currency']),
                'amount' => $price['amount'],
                'season_id' => $price['season_id'] ?? null,
            ];
        }, $prices);
    }
}
