<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Propaganistas\LaravelPhone\PhoneNumber;
use Propaganistas\LaravelPhone\Rules\Phone;
use Throwable;
class RegisterRequest extends ApiFormRequest
{
     protected function prepareForValidation(): void
    {
        if (! $this->filled('phone_number')) {
            return;
        }

        try {
            $this->merge([
                'phone_number' => (new PhoneNumber($this->string('phone_number')->toString()))
                    ->formatE164(),
            ]);
        } catch (Throwable) {
            // Leave the original value as-is so the validator can return the proper error.
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users'],
            'phone_number' => ['required',
                'string',
                'max:25',
                (new Phone)->international(),
                'unique:users'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)
                                                                       ->max(70)
                                                                       ->letters()
                                                                       ->mixedCase()
                                                                       ->numbers()
                                                                       ->symbols()],
            'fcm_token' => ['nullable', 'string', 'max:255', Rule::unique('devices', 'fcm_token')], //just until we add firebase notifications
        ];
    }
}
