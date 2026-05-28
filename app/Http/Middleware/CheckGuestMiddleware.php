<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Device;
use Illuminate\Support\Facades\Auth;

class CheckGuestMiddleware extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('sanctum')->check()) {
            return $this->failed( __('You are already authenticated'), 409);
        }

        $device = Device::where('identifier', $request->header('device'))->first();

        if ($device && $device->refresh_token && now()->lt($device->token_expires_at)) {
            return $this->failed(__('You are already authenticated'), 409);
        }
        

        return $next($request);
    }

}
