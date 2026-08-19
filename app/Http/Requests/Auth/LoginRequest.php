<?php

namespace App\Http\Requests\Auth;

use App\Enums\Role;
use App\Http\Requests\Api\ApiFormRequest;
use App\Models\User;
use App\Services\LoggingServices\NormalAuthenticationLoggingService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class LoginRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:70'],
            'fcm_token' => ['nullable', 'string', 'max:255', Rule::unique('devices', 'fcm_token')],
            'remember_me' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
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
            ->whereRelation('roles', 'name', Role::USER->value)
            ->first();

        if (! $user ||
        ! Hash::check($this->password, $user->password)) {
            app(NormalAuthenticationLoggingService::class)->failed_login([
                'email' => $this->string('email')->toString(),
                'user_agent' => $this->userAgent(),
                'ip' => $this->ip(),
            ]);

            return $this->failed(__('Invalid credentials. Check your email and password and try again.'), 401);
        }

        if ($user->bannedUser()->exists()) {
            return $this->failed(__('Your account has been banned.'), 401);
        }

        return null;
    }
}
