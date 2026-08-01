<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        if ($response = $request->check_credentials()) {
            return $response;
        }

        $user = User::query()
            ->where('email', $request->string('email')->toString())
            ->whereRelation('roles', 'name', Role::ADMIN->value)
            ->firstOrFail();

        Auth::guard('web')->login($user, $request->boolean('remember_me'));
        $request->session()->regenerate();

        return $this->succeed(__('Admin logged in successfully.'), [], 201);
    }

    public function logout(): JsonResponse
    {
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return $this->succeed(__('Admin logged out successfully.'));
    }
}
