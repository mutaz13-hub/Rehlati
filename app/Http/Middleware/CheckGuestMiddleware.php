<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Device;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class CheckGuestMiddleware extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
          if(Auth::guard('sanctum')->check()){
            return $this->failed( __('You are already authenticated'));
        }

        $device = Device::where('identifier', $request->header('device'))->first();

        if(isset($device)){
        $valid_refresh_token = DB::table('personal_refresh_tokens')
                                  ->where('device_id', $device->id)
                                  ->where('expires_at', '>', now())
                                  ->first();

        if(isset($valid_refresh_token)){
            return $this->failed(__('You are already authenticated'));
        }

    }

        return $next($request);
}

}
