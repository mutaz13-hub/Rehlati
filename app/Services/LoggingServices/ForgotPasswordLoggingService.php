<?php

namespace App\Services\LoggingServices;

use Illuminate\Support\Facades\Log;

class ForgotPasswordLoggingService
{
    /**
     * @param array{email: string, user_agent: string, ip: string} $data
     */
    public function requested_password_code_for_available_email(array $data): void
    {
        $this->log("User Requested Passowrd Reset Code For Available Email", "notice", $data);
    }

    /**
     * @param array{email: string, user_agent: string, ip: string} $data
     */
    public function requested_password_code_for_banned_email(array $data): void
    {
        $this->log("Banned User Requested Password Reset Code", "notice", $data);
    }

    /**
     * @param array{email: string, user_agent: string, ip: string} $data
     */
    public function requested_password_code_for_social_email(array $data): void
    {
        $this->log("User Requested Password Reset Code For Social Email", "warning", $data);
    }

    /**
     * @param array{email: string, user_agent: string, ip: string} $data
     */
    public function requested_password_code_for_unknown_email(array $data): void
    {
        $this->log("User Requested Password Reset Code For Unknown Email", "warning", $data);
    }

    /**
     * @param array{email: string, user_agent: string, ip: string} $data
     */
    public function failed_reset_validation(array $data): void
    {
        $this->log("User Failed to validate Password Reset Code", "notice", $data);
    }

    /**
     * @param array{email: string, user_agent: string, ip: string} $data
     */
    public function success_reset_validation(array $data): void
    {
        $this->log("User Successfully Validated Password Reset Code", "info", $data);
    }

    /**
     * @param array{email: string, user_agent: string, ip: string} $data
     */
    public function no_existing_reset_record(array $data): void
    {
        $this->log("User Tried To Reset Password For Non Existing Record", 'warning', $data);
    }

    /**
     * @param array{email: string, user_agent: string, ip: string} $data
     */
    public function wrong_token_on_reset(array $data): void
    {
        $this->log("User With Wrong Token Tried To Reset Password", 'warning', $data);
    }

    /**
     * @param array{email: string, user_agent: string, ip: string} $data
     */
    public function expired_session(array $data): void
    {
        $this->log("User Tried To Reset Password, But The Token Has Expired", 'notice', $data);
    }

    /**
     * @param array{email: string, user_agent: string, ip: string} $data
     */
    public function reset_password_success(array $data): void
    {
        $this->log("User Reset Password Successfully", 'info', $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function log(string $message, string $level, array $data): void
    {
        Log::channel('auth')->log($level, $message, $data);
    }
}
