<?php

namespace App\Services\LoggingServices;

use Illuminate\Support\Facades\Log;

class NormalAuthenticationLoggingService
{
    /**
     * @param array{user_id: int, user_agent: string, ip: string} $data
     */
    public function register(array $data): void
    {
        $this->log("A New User Registered", "info", $data);
    }

    /**
     * @param array{user_id: int, user_agent: string, ip: string} $data
     */
    public function login(array $data): void
    {
        $this->log('User Logged In To His Account', "info", $data);
    }

    /**
     * @param array{email: string, user_agent: string, ip: string} $data
     */
    public function failed_login(array $data): void
    {
        $this->log("Failed Login Attempt", "notice", $data);
    }

    /**
     * @param array{user_id: int, user_agent: string, ip: string} $data
     */
    public function logout(array $data): void
    {
        $this->log('User Logged Out From His Account', "info", $data);
    }

    /**
     * @param array{user_id: int, user_agent: string, ip: string} $data
     */
    public function logout_from_all_devices(array $data): void
    {
        $this->log("User Logged Out From All His Devices", "info", $data);
    }

    /**
     * @param array{user_id: int, user_agent: string, ip: string} $data
     */
    public function logout_from_other_devices(array $data): void
    {
        $this->log("User Logged Out From Other Devices", "info", $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function log(string $message, string $level, array $data): void
    {
        Log::channel('auth')->log($level, $message, $data);
    }
}
