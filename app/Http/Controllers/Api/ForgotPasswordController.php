<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\ValidateResetCodeRequest;
use App\Services\ForgotPasswordService;
use Illuminate\Http\JsonResponse;

class ForgotPasswordController extends Controller
{
    public function __construct(protected ForgotPasswordService $forgotPasswordService) {}

    /**
     * Send password reset code to email.
     */
    public function forgot_password(ForgotPasswordRequest $request): JsonResponse
    {
        $this->forgotPasswordService->send_resetting_code($request->validated());

        return $this->succeed(__('if this email is registered, a password reset code has been sent to it.'), [], 202);
    }

    /**
     * Validate password reset code.
     */
    public function validate_reset_code(ValidateResetCodeRequest $request): JsonResponse
    {
        if ($this->forgotPasswordService->validate_password_resetting_code($request->validated())) {
            return $this->succeed(__('Password reset code is valid.'));
        }

        return $this->failed(__('Invalid or Expired reset code.'), 401);
    }

    /**
     * Reset user password.
     */
    public function reset_password(ResetPasswordRequest $request): JsonResponse
    {
        $result = $this->forgotPasswordService->reset_password($request->validated());

        if ($result['status']) {
            return $this->succeed($result['message']);
        }

        return $this->failed($result['message'], 400);
    }
}
