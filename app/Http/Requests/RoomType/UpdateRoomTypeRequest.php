<?php

namespace App\Http\Requests\RoomType;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateRoomTypeRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && method_exists($user, 'hasRole') && $user->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'name_en' => 'sometimes|required|string|max:255',
            'name_ar' => 'sometimes|required|string|max:255',
        ];
    }
}
