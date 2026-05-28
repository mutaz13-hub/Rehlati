<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\LoggingServices\RefreshLoggingService;
use Illuminate\Support\Facades\Auth;
use App\Models\Device;

class EnsureCanRefreshMiddleware extends BaseMiddleware
{
    public function __construct(
        private RefreshLoggingService $logging_service
    ) {}
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(Auth::guard('sanctum')->check()){

            $this->logging_service->already_authenticated([
                'user_id' => $request->user('sanctum')->id,
                'user_agent' => $request->userAgent(),
                'ip' => maskIp($request->ip())
            ]);

            return $this->failed(__('You are already authenticated'), 409);
        }

        $device = Device::where('identifier', $request->header('device'))->first();

        if(!isset($device)){
            $this->logging_service->un_valid_device([
                'user_agent' => $request->userAgent(),
                'ip' => maskIp($request->ip())
            ]);

            return $this->failed(__('Un valid device'), 401);

            if(now()->gt($device->token_expires_at)){
                $this->logging_service->un_valid_or_expired_refresh_token([
                    'user_agent' => $request->userAgent(),
                    'ip' => maskIp($request->ip())
                ]);

                return $this->failed(__('Un valid or expired refresh token'), 401);
            }


        }

        return $next($request);
    }
}
