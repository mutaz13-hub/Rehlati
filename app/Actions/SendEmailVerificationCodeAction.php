<?php

namespace App\Actions;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SendEmailVerificationCodeAction
{
    public function execute(User $user): void
    {
        $otp = Str::random(6);

        try {
            Cache::put("verification_code_{$user->id}", $otp, now()->addMinutes(10));

            $user->notify(
                (new VerifyEmailNotification($user->name, $otp))
                    ->locale(app()->getLocale())
            );
        } catch (\Throwable $exception) {
            Cache::forget("verification_code_{$user->id}");

            throw $exception;
        }
    }
}
