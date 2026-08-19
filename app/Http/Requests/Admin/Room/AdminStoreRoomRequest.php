<?php

namespace App\Http\Requests\Admin\Room;

use App\Enums\RoomClass;
use App\Enums\RoomLayout;
use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class AdminStoreRoomRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && method_exists($user, 'hasRole') && $user->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:255', Rule::unique('rooms', 'name_en')->where(function ($query) {
                return $query->where('hotel_id', $this->route('hotel')->id);
            })],
            'name_ar' => ['required', 'string', 'max:255', Rule::unique('rooms', 'name_ar')->where(function ($query) {
                return $query->where('hotel_id', $this->route('hotel')->id);
            })],

            'room_class' => ['required', 'string', Rule::enum(RoomClass::class)],
            'room_layout' => ['required', 'string', Rule::enum(RoomLayout::class)],

            'max_adults' => ['required', 'integer', 'min:1'],
            'max_children' => ['required', 'integer', 'min:0'],
            'max_guests' => ['required', 'integer', 'gte:max_adults', 'gte:max_children', function ($attribute, $value, $fail) {
                $maxAdults = (int) $this->input('max_adults', 0);
                $maxChildren = (int) $this->input('max_children', 0);
                $combinedSum = $maxAdults + $maxChildren;

                if ($value > $combinedSum) {
                    $fail(__("The max guests cannot be greater than the combined sum of adults and children ($combinedSum)."));
                }
            }],

            'total_rooms' => ['required', 'integer', 'min:0'],
            'available_rooms' => ['required', 'integer', 'min:0', 'lte:total_rooms'],
            'description_en' => ['nullable', 'string', 'max:10000'],
            'description_ar' => ['nullable', 'string', 'max:10000'],

            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['integer', 'exists:amenities,id'],

            'beds' => ['required', 'array', 'min:1'],
            'beds.*.bed_type_id' => ['required', 'integer', 'exists:bed_types,id', 'distinct'],
            'beds.*.quantity' => ['required', 'integer', 'min:1'],
            'beds.*.assigned_capacity' => ['required', 'integer', 'min:1'],

            'prices' => ['required', 'array', 'min:1'],
            'prices.*.price_type' => ['required', 'string', Rule::in(['base_price', 'child_price', 'extra_bed_price'])],
            'prices.*.nationality_category' => ['required', 'string', Rule::in(['syrian', 'expat', 'foreigner'])],
            'prices.*.currency' => ['required', 'string', Rule::in(['USD'])],
            'prices.*.amount' => ['required', 'numeric', 'min:0'],
            'prices.*.season_id' => ['nullable', 'integer', Rule::exists('seasons', 'id')->where(function ($query) {
                return $query->where('seasonable_type', 'hotel')
                    ->where('seasonable_id', $this->route('hotel')->id);
            })],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {

            $prices = $this->input('prices', []);
            $priceKeys = [];
            foreach ($prices as $index => $price) {
                $key = implode('|', [
                    $price['price_type'] ?? '',
                    $price['nationality_category'] ?? '',
                    $price['season_id'] ?? 'NULL',
                ]);
                if (isset($priceKeys[$key])) {
                    $validator->errors()->add(
                        "prices.{$index}",
                        __('Duplicate price tier — same price_type, nationality_category, and season_id already provided')
                    );
                }
                $priceKeys[$key] = true;
            }
        });
    }
}
