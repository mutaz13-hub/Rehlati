<?php

namespace App\Services\LoggingServices;

use Illuminate\Support\Facades\Log;

class EmailVerificationLoggingService
{
    /**
     * @param  array{user_id: int, user_agent: string, ip: string}  $data
     */
    public function verified(array $data): void
    {
        $this->log('User Verified their email', 'info', $data);
    }

    /**
     * @param  array{user_id: int, user_agent: string, ip: string}  $data
     */
    public function wrong_verification(array $data): void
    {
        $this->log('User Entered Wrong Or Expired Verification Code', 'info', $data);
    }

    /**
     * @param  array{user_id: int, user_agent: string, ip: string}  $data
     */
    public function verification_code_request(array $data): void
    {
        $this->log('User Requested Verification Code', 'notice', $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function log(string $message, string $level, array $data): void
    {
        Log::channel('auth')->log($level, $message, $data);
    }
}
