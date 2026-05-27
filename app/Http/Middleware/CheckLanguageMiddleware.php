<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckLanguageMiddleware extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
         $request_lang = $request->header('lang') ?? 'ar';

        if(!in_array($request_lang, config('app.supported_locales'))){
            return $this->failed(__('Un supported language'));
        }

        app()->setLocale($request_lang);

         $id = auth()->id();

        if ($id) {
            Cache::put('lang_for_user: ' . $id, $request_lang, now()->addDays(30));
        }
        
        return $next($request);
    }
}
