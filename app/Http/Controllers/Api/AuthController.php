<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailVerificationRequest;
use App\Http\Requests\Auth\GoogleLoginRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use App\Services\GoogleAuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected GoogleAuthenticationService $googleAuthenticationService,
    ) {}

    /**
     * Handle user registration.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->succeed(
            __('User registered successfully. Please verify your account.'),
            $result,
            201
        );
    }

    /**
     * Handle user login.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if ($response = $request->check_credentials()) {
            return $response;
        }

        $data = $this->authService->login($request->validated());

        return $this->succeed($data['message'], [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'device' => $data['device'],
        ], 201);
    }

    /**
     * Handle Google social login.
     */
    public function google_login(GoogleLoginRequest $request): JsonResponse
    {
        $result = $this->googleAuthenticationService->google_login($request->validated());

        if (! $result['status']) {
            return $this->failed($result['message'], 401);
        }

        return $this->succeed($result['message'], [
            'access_token' => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
            'device' => $result['device'],
        ], 201);
    }

    /**
     * Refresh access token.
     */
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $result = $this->authService->refresh($request->validated());

        if (! $result['status']) {
            return $this->failed($result['data'], 401);
        }

        return $this->succeed(__('Access token refreshed successfully.'), [
            'access_token' => $result['data'],
        ], 201);
    }

    /**
     * Verify email with the provided code.
     */
    public function verify_email(EmailVerificationRequest $request): JsonResponse
    {
        if ($this->authService->verify_email(Auth::user(), $request->validated('code'))) {
            return $this->succeed(__('Your email has been verified successfully.'));
        }

        return $this->failed(__('Invalid or Expired verification code.'), 401);
    }

    /**
     * Resend verification code to user.
     */
    public function resend_verification_code(): JsonResponse
    {
        $this->authService->resend_verification_code(Auth::user());

        return $this->succeed(__('A verification code has been sent to your account.'), [], 202);
    }

    /**
     * Logout from current device.
     */
    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return $this->succeed(__('You have been logged out successfully.'));
    }

    /**
     * Logout from all devices except current one.
     */
    public function logout_other_devices(): JsonResponse
    {
        $this->authService->logout_other_devices(Auth::user());

        return $this->succeed(__('You have been logged out from all other devices.'));
    }

    /**
     * Logout from all devices.
     */
    public function logout_all_devices(): JsonResponse
    {
        $this->authService->logout_all_devices(Auth::user());

        return $this->succeed(__('You have been logged out from all devices.'));
    }
}
