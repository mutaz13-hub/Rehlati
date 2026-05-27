<?php

namespace App\Services;

use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DeviceTokensService
{
    /**
     * @param User $user
     * @param string $tokenName
     * @param string $fcmToken
     * @param \DateTimeInterface $refreshTokenExpiresAt
     *
     * @return array{access_token: string, refresh_token: string, device: string}
     */
    public function issueTokenPair(User $user, string $tokenName, string $fcmToken = null, \DateTimeInterface $refreshTokenExpiresAt): array
    {
        $refreshToken = Str::random(64);
        $salt = Str::random(32);

        $device = Device::create([
            'identifier' => Str::random(60),
            'refresh_token' => hash('sha256', $refreshToken.$salt),
            'salt' => $salt,
            'token_expires_at' => $refreshTokenExpiresAt,
            'fcm_token' => $fcmToken,
        ]);

        return [
            'access_token' => $this->createAccessTokenForDevice($user, $device, $tokenName),
            'refresh_token' => $refreshToken,
            'device' => $device->identifier,
        ];
    }

    public function createAccessTokenForDevice(User $user, Device $device, string $tokenName = 'login_token'): string
    {
        $token = $user->createToken(
            $tokenName,
            ['*'], 
            now()->addMinutes(config('sanctum.expiration'))
        );

        $token->accessToken->forceFill(['device_id' => $device->id])->save();

        return $token->plainTextToken;
    }

}