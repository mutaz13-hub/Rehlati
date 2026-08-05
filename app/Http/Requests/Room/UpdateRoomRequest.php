<?php

namespace App\Http\Requests\Room;

use App\Enums\BedType;
use App\Enums\RoomType;
use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;
class UpdateRoomRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && method_exists($user, 'hasRole') && $user->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'name_en' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('rooms', 'name_en')->where(function ($query) {
                return $query->where('hotel_id', $this->route('hotel')->id);
            })->ignore($this->route('room')->id)],
            'name_ar' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('rooms', 'name_ar')->where(function ($query) {
                return $query->where('hotel_id', $this->route('hotel')->id);
            })->ignore($this->route('room')->id)],
            'room_type' => ['sometimes', 'required', 'string', Rule::enum(RoomType::class)],
            'bed_type' => ['sometimes', 'required', 'string', Rule::enum(BedType::class)],
            'total_rooms' => ['sometimes', 'required', 'integer', 'min:0'],
            'available_rooms' => ['sometimes', 'nullable', 'integer', 'min:0'],

            'prices' => ['sometimes', 'required', 'array', 'min:1'],
            'prices.*.price_type' => ['required_with:prices', 'string', Rule::in(['base_price', 'extra_bed_price', 'package_price'])],
            'prices.*.nationality_category' => ['required_with:prices', 'string', Rule::in(['syrian', 'expat', 'foreigner'])],
            'prices.*.currency' => ['required_with:prices', 'string', Rule::in(['SYP', 'USD', 'EUR'])],
            'prices.*.amount' => ['required_with:prices', 'numeric', 'min:0'],
            'prices.*.season_id' => ['nullable', 'integer', 'exists:seasons,id'],
        ];
    }
}
