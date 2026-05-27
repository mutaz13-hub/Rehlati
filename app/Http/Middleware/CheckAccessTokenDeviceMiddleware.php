<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccessTokenDeviceMiddleware extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $access_token = $request->user()->currentAccessToken();

        $device = Device::findOrFail($access_token->device_id);

        if($request->header('device') != $device->identifier){

            $device->delete();

            return $this->failed(__('For Security Reasons, We Want You To Login Again Please'), 401);
        }
        return $next($request);
    }
}
