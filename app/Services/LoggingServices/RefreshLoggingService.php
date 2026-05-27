<?php

namespace App\Services\LoggingServices;

use Illuminate\Support\Facades\Log;

class RefreshLoggingService
{
    /**
     * @param array{user_id: int, user_agent: string, ip: string} $data
     */
    public function already_authenticated(array $data): void
    {
        $this->log("An Attempt to refresh token but user is already authenticated", "warning", $data);
    }

    /**
     * @param array{user_agent: string, ip: string} $data
     */
    public function un_valid_device(array $data): void
    {
        $this->log("Un valid device is detected while trying to refresh", "warning", $data);
    }

    /**
     * @param array{user_agent: string, ip: string} $data
     */
    public function un_valid_or_expired_refresh_token(array $data): void
    {
        $this->log("Un valid or expired refresh token is detected while trying to refresh", "warning", $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function log(string $message, string $level, array $data): void
    {
        Log::channel('auth')->log($level, $message, $data);
    }
}
