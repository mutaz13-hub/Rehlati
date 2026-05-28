<?php

namespace App\Services\LoggingServices;

use Illuminate\Support\Facades\Log;

class SocialAuthenticationLoggingService
{
    /**
     * @param array{social_provider: string, attempted_provider: string, email: string, user_agent: string, ip: string} $data
     */
    public function social_login_provider_mismatch(array $data): void
    {
        $this->log('Social Login Provider Mismatch', 'notice', $data);
    }

    /**
     * @param array{social_provider: string, email: string, user_agent: string, ip: string} $data
     */
    public function unverified_social_account(array $data): void
    {
        $this->log('Unverified Social Account Login Attempt', 'notice', $data);
    }

    /**
     * @param array{user_id: int, social_provider: string, user_agent: string, ip: string} $data
     */
    public function login(array $data): void
    {
        $this->log('User Logged In Via Social Provider', 'info', $data);
    }

    /**
     * @param array{social_provider: string, email: string, user_agent: string, ip: string} $data
     */
    public function invalid_id_token(array $data): void
    {
        $this->log('Invalid Social ID Token', 'notice', $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function log(string $message, string $level, array $data): void
    {
        Log::channel('auth')->log($level, $message, $data);
    }
}
