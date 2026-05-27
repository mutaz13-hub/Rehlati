<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'code' => ['required', 'string', 'size:6'],
            'new_password' => ['required', 'string', 'confirmed', Password::min(8)
                                                                       ->max(70)
                                                                       ->letters()
                                                                       ->mixedCase()
                                                                       ->numbers()
                                                                       ->symbols()],
        ];
    }
}
