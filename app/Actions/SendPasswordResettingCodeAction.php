<?php

namespace App\Actions;

use App\Models\User;
use App\Notifications\SendPasswordResetCodeNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SendPasswordResettingCodeAction
{
    public function execute(User $user): void
    {
        $token = Str::random(6);

            DB::transaction(function () use ($user, $token) {
                DB::table('password_reset_tokens')
                    ->where('email', $user->email)
                    ->delete();

                DB::table('password_reset_tokens')->insert([
                    'email' => $user->email,
                    'token' => Hash::make($token),
                    'created_at' => now(),
                ]);
            });

            $user->notify(
                (new SendPasswordResetCodeNotification($token, $user->name))
                    ->locale(app()->getLocale())
            );
       
    }
}
