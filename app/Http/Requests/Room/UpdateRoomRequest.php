<?php

namespace App\Http\Requests\Room;

use App\Http\Requests\Api\ApiFormRequest;

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
            'room_type_id' => 'sometimes|required|integer|exists:room_types,id',
            'price_per_night' => 'sometimes|required|numeric|min:0',
            'total_rooms' => 'sometimes|required|integer|min:0',
            'available_rooms' => 'nullable|integer|min:0',
        ];
    }
}
