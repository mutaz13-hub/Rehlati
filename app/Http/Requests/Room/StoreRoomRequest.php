<?php

namespace App\Http\Requests\Room;

use App\Enums\BedType;
use App\Enums\RoomType;
use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;
class StoreRoomRequest extends ApiFormRequest
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
            'room_type' => ['required', 'string', Rule::enum(RoomType::class)],
            'bed_type' => ['required', 'string', Rule::enum(BedType::class)],
            'price_per_night' => ['required', 'numeric', 'min:0'],
            'total_rooms' => ['required', 'integer', 'min:0'],
            'available_rooms' => ['required', 'integer', 'min:0'],
        ];
    }
}
