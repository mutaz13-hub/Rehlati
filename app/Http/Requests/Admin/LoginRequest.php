<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use App\Http\Requests\Api\ApiFormRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class LoginRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:70'],
            'remember_me' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.max' => __('Invalid credentials. Check your email and password and try again.'),
            'email.max' => __('Invalid credentials. Check your email and password and try again.'),
        ];
    }

    public function check_credentials(): ?JsonResponse
    {
        $user = User::query()
            ->where('email', $this->string('email')->toString())
            ->whereRelation('roles', 'name', Role::ADMIN->value)
            ->first();

        if (! $user || ! Hash::check($this->password, $user->password)) {
            return $this->failed(__('Invalid credentials. Check your email and password and try again.'), 401);
        }

        if ($user->bannedUser()->exists()) {
            return $this->failed(__('Your account has been banned.'), 401);
        }

        return null;
    }
}
