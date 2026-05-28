<?php

namespace App\Services;

use App\Models\Device;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

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

    /**
     * Refresh access token for a device using a refresh token.
     *
     * @param Device $device
     * @param string $refreshToken
     * @param string $tokenName
     * @return string|null
     */
    public function refreshAccessToken(Device $device, array $data): ?string
    {
        if (Carbon::parse($device->token_expires_at)->isPast()) {
           
            return null;
        }

        $hashedRefreshToken = hash('sha256', $data['refresh_token'] . $device->salt);

        if (! hash_equals($device->refresh_token, $hashedRefreshToken)) {
            
            return null;
        }

        $user = $device->accessTokens()->latest('id')->first()?->tokenable;

        if (! $user instanceof User) {
            return null;
        }

        $device->accessTokens()->delete();

        return $this->createAccessTokenForDevice($user, $device, 'after_refresh');
    }
}
