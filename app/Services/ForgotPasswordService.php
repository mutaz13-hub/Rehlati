<?php

namespace App\Services;

use App\Actions\SendPasswordResettingCodeAction;
use App\Enums\Role;
use App\Models\Device;
use App\Models\User;
use App\Services\LoggingServices\ForgotPasswordLoggingService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ForgotPasswordService
{
    public function __construct(
        protected SendPasswordResettingCodeAction $sendPasswordResettingCodeAction,
        protected ForgotPasswordLoggingService $forgotPasswordLoggingService
    ) {}

    /**
     * @param  array{email: string}  $data
     */
    public function send_resetting_code(array $data): void
    {
        $user = User::query()
            ->whereRelation('roles', 'name', Role::USER->value)
            ->where('email', $data['email'])
            ->first();

        if ($user) {
            $this->sendPasswordResettingCodeAction->execute($user);
            $this->forgotPasswordLoggingService->requested_password_code_for_available_email([
                'email' => maskEmail($data['email']),
                'user_agent' => request()->userAgent(),
                'ip' => maskIp(request()->ip()),
            ]);
        } else {
            $this->forgotPasswordLoggingService->requested_password_code_for_unknown_email([
                'email' => maskEmail($data['email']),
                'user_agent' => request()->userAgent(),
                'ip' => maskIp(request()->ip()),
            ]);
        }
    }

    /**
     * @param  array{email: string, code: string}  $data
     */
    public function validate_password_resetting_code(array $data): bool
    {
        $token = DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->first();

        if ($token && Hash::check($data['code'], $token->token) && now()->diffInMinutes($token->created_at) * -1 <= config('auth.passwords.users.expire')) {
            $this->forgotPasswordLoggingService->success_reset_validation([
                'email' => maskEmail($data['email']),
                'user_agent' => request()->userAgent(),
                'ip' => maskIp(request()->ip()),
            ]);

            return true;
        }

        $this->forgotPasswordLoggingService->failed_reset_validation([
            'email' => maskEmail($data['email']),
            'user_agent' => request()->userAgent(),
            'ip' => maskIp(request()->ip()),
        ]);

        return false;
    }

    /**
     * @param  array{email: string, code: string, new_password: string}  $data
     * @return array{status: bool, message: string}
     */
    public function reset_password(array $data): array
    {
        $available_token = DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->first();

        $message = __('For Security reasons, please retry again later');

        $status = false;

        if (! $available_token) {
            $this->forgotPasswordLoggingService->no_existing_reset_record([
                'email' => maskEmail($data['email']),
                'user_agent' => request()->userAgent(),
                'ip' => maskIp(request()->ip()),
            ]);
        } elseif (! Hash::check($data['code'], $available_token->token)) {
            $this->forgotPasswordLoggingService->wrong_token_on_reset([
                'email' => maskEmail($data['email']),
                'user_agent' => request()->userAgent(),
                'ip' => maskIp(request()->ip()),
            ]);
        } elseif ((now()->diffInMinutes($available_token->created_at) * -1) >= config('auth.passwords.users.expire')) {
            $message = __('Sorry, your session has expired');
            $this->forgotPasswordLoggingService->expired_session([
                'email' => maskEmail($data['email']),
                'user_agent' => request()->userAgent(),
                'ip' => maskIp(request()->ip()),
            ]);
        } else {
            DB::table('password_reset_tokens')
                ->where('email', $data['email'])
                ->delete();

            $user = User::query()
                ->where('email', $data['email'])
                ->firstOrFail();

            $user->update([
                'password' => bcrypt($data['new_password']),
            ]);

            event(new PasswordReset($user));

            // Logout user from all devices for security
            $deviceIds = $user->tokens()->pluck('device_id')->toArray();
            if (! empty($deviceIds)) {
                Device::whereIn('id', $deviceIds)->delete();
            }

            $status = true;

            $message = __('Your password has been reset successfully');
            $this->forgotPasswordLoggingService->reset_password_success([
                'email' => maskEmail($data['email']),
                'user_agent' => request()->userAgent(),
                'ip' => maskIp(request()->ip()),
            ]);
        }

        return [
            'status' => $status,
            'message' => $message,
        ];
    }
}
