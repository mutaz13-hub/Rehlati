<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class GoogleLoginRequest extends ApiFormRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id_token' => ['required', 'string'],
            'fcm_token' => ['nullable', 'string', 'max:255', Rule::unique('devices', 'fcm_token')],
        ];
    }
}
