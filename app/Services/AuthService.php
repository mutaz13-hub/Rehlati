<?php

namespace App\Services;

use App\Actions\SendEmailVerificationCodeAction;
use App\Enums\Role;
use App\Models\Device;
use App\Models\User;
use App\Services\LoggingServices\EmailVerificationLoggingService;
use App\Services\LoggingServices\NormalAuthenticationLoggingService;
use App\Services\LoggingServices\RefreshLoggingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AuthService
{
    public function __construct(
        protected DeviceTokensService $tokens_manager,
        protected SendEmailVerificationCodeAction $sendEmailVerificationCodeAction,
        protected NormalAuthenticationLoggingService $authenticationLoggingService,
        protected EmailVerificationLoggingService $emailVerificationLoggingService,
        protected RefreshLoggingService $refreshLoggingService
    ){}

    /**
     * Register a new user.
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array
    {
       return DB::transaction(function() use ($data) {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'password' => bcrypt($data['password']),
        ]);

        $user->assignRole(Role::USER->value);

        $this->sendEmailVerificationCodeAction->execute($user);
        $this->emailVerificationLoggingService->verification_code_request([
            'user_id' => $user->id,
            'user_agent' => request()->userAgent(),
            'ip' => maskIp(request()->ip()),
        ]);

        $tokens = $this->tokens_manager->issueTokenPair(
                $user,
                'registration_token',
                $data['fcm_token'] ?? null,
                now()->addDays(config('app.refresh_token_expiration_days_WITHOUT_remember_me'))
            );

        $this->authenticationLoggingService->register([
            'user_id' => $user->id,
            'user_agent' => request()->userAgent(),
            'ip' => maskIp(request()->ip()),
        ]);

        return [
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'device' => $tokens['device'],
        ];

        });
    }

    /**
     * Authenticate a user.
     *
     * @param array $data
     * @return array
     */
    public function login(array $data): array
    {
        return DB::transaction(function() use ($data) {
            $user = User::query()
                    ->where('email', $data['email'])
                    ->whereRelation('roles', 'name', Role::USER->value)
                    ->firstOrFail();

            $tokens = $this->tokens_manager->issueTokenPair(
                    $user,
                    'login_token',
                    $data['fcm_token'] ?? null,
                    $data['remember_me']
                        ? now()->addDays(config('app.refresh_token_expiration_days_with_remember_me'))
                        : now()->addDays(config('app.refresh_token_expiration_days_WITHOUT_remember_me'))
                );

             $message = __('You have successfully logged in.');

            if (! $user->email_verified_at) {
                $message = __('Please verify your email to complete the login process. A verification code has been sent to your email.');

                $this->sendEmailVerificationCodeAction->execute($user);
                $this->emailVerificationLoggingService->verification_code_request([
                    'user_id' => $user->id,
                    'user_agent' => request()->userAgent(),
                    'ip' => maskIp(request()->ip()),
                ]);
            }

            $this->authenticationLoggingService->login([
                'user_id' => $user->id,
                'user_agent' => request()->userAgent(),
                'ip' => maskIp(request()->ip()),
            ]);

            return [
                'message' => $message,
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'device' => $tokens['device'],
            ];
        });
    }

    /**
     * Verify email with the provided code.
     *
     * @param User $user
     * @param string $code
     * @return bool
     */
    public function verify_email(User $user, string $code): bool
    {
        $cached_code = Cache::get("verification_code_{$user->id}");

        if ($cached_code && $cached_code === $code) {
            $user->update([
                'email_verified_at' => now()
            ]);

            Cache::forget("verification_code_{$user->id}");

            $this->emailVerificationLoggingService->verified([
                'user_id' => $user->id,
                'user_agent' => request()->userAgent(),
                'ip' => maskIp(request()->ip()),
            ]);

            return true;
        }

        $this->emailVerificationLoggingService->wrong_verification([
            'user_id' => $user->id,
            'user_agent' => request()->userAgent(),
            'ip' => maskIp(request()->ip()),
        ]);

        return false;
    }

    /**
     * Resend verification code to user.
     *
     * @param User $user
     * @return void
     */
    public function resend_verification_code(User $user): void
    {
        $this->sendEmailVerificationCodeAction->execute($user);
        $this->emailVerificationLoggingService->verification_code_request([
            'user_id' => $user->id,
            'user_agent' => request()->userAgent(),
            'ip' => maskIp(request()->ip()),
        ]);
    }

    /**
     * Logout from current device.
     *
     * @param string $deviceIdentifier
     * @return void
     */
    public function logout(): void
    {
        $device = Device::where('identifier', request()->header('device'))->firstOrFail();
        $device->delete();

        $this->authenticationLoggingService->logout([
            'user_id' => auth()->id(),
            'user_agent' => request()->userAgent(),
            'ip' => maskIp(request()->ip()),
        ]);
    }

    /**
     * Logout from all devices except current one.
     *
     * @param User $user
     * @return void
     */
    public function logout_other_devices(User $user): void
    {
        $currentDeviceId = $user->currentAccessToken()->device_id;

        $devices_to_delete = $user->tokens()->where('device_id', '!=', $currentDeviceId)->pluck('device_id')->toArray();

        if (!empty($devices_to_delete)) {
            Device::whereIn('id', $devices_to_delete)->delete();
        }

        $this->authenticationLoggingService->logout_from_other_devices([
            'user_id' => $user->id,
            'user_agent' => request()->userAgent(),
            'ip' => maskIp(request()->ip()),
        ]);
    }

    /**
     * Logout from all devices.
     *
     * @param User $user
     * @return void
     */
    public function logout_all_devices(User $user): void
    {
        $deviceIds = $user->tokens()->pluck('device_id')->toArray();

        if (!empty($deviceIds)) {
            Device::whereIn('id', $deviceIds)->delete();
        }

        $this->authenticationLoggingService->logout_from_all_devices([
            'user_id' => $user->id,
            'user_agent' => request()->userAgent(),
            'ip' => maskIp(request()->ip()),
        ]);
    }

    /**
     * Refresh access token for current device.
     *
     * @param array{refresh_token: string} $data
     * @return array{status: bool, data: string}
     */
    public function refresh(array $data): array
    {
        $device = Device::where('identifier', request()->header('device'))->first();

        if (! $device) {
            $this->refreshLoggingService->un_valid_device([
                'user_agent' => request()->userAgent(),
                'ip' => maskIp(request()->ip()),
            ]);

            return [
                'status' => false,
                'data' => __('Invalid device. Please login again.'),
            ];
        }

        $accessToken = $this->tokens_manager->refreshAccessToken($device, $data);

        if (! $accessToken) {
            $this->refreshLoggingService->un_valid_or_expired_refresh_token([
                'user_agent' => request()->userAgent(),
                'ip' => maskIp(request()->ip()),
            ]);

            return [
                'status' => false,
                'data' => __('Invalid or expired refresh token.'),
            ];
        }

        return [
            'status' => true,
            'data' => $accessToken,
        ];
    }
}
