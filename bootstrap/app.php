<?php

use App\Http\Middleware\CheckAccessTokenDeviceMiddleware;
use App\Http\Middleware\CheckApiPasswordMiddleware;
use App\Http\Middleware\CheckEmailNotVerifiedMiddleware;
use App\Http\Middleware\CheckEmailVerifiedMiddleware;
use App\Http\Middleware\CheckGuestMiddleware;
use App\Http\Middleware\CheckLanguageMiddleware;
use App\Http\Middleware\EnsureCanRefreshMiddleware;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(CheckLanguageMiddleware::class);
        $middleware->statefulApi();
        $middleware->alias([
            'check_api_password' => CheckApiPasswordMiddleware::class,
            'check_language' => CheckLanguageMiddleware::class,
            'check_access_token_device' => CheckAccessTokenDeviceMiddleware::class,
            'check_guest' => CheckGuestMiddleware::class,
            'check_email_not_verified' => CheckEmailNotVerifiedMiddleware::class,
            'check_email_verified' => CheckEmailVerifiedMiddleware::class,
            'ensure_can_refresh' => EnsureCanRefreshMiddleware::class,
            'admin' => AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
         $shouldReturnJson = static fn (Request $request): bool => $request->expectsJson() || $request->is('api/*');

        $setRequestLocale = static function (Request $request): void {
            $locale = $request->header('lang');

            if ($locale && in_array($locale, config('app.supported_locales', []), true)) {
                app()->setLocale($locale);
            }
        };

        $apiError = static function (
            int $status,
            string $message
        )  {

            return response()->json([
                'message' => $message,
            ], $status);
        };

         $exceptions->render(function (AuthenticationException $e, Request $request) use ($shouldReturnJson, $apiError, $setRequestLocale) {
             if (! $shouldReturnJson($request)) {
                 return null;
             }

             $setRequestLocale($request);

             return $apiError(
                401,
                 __('You are not authenticated, please login and try again'),
             );
         });

        // $exceptions->render(function (AuthorizationException $e, Request $request) use ($shouldReturnJson, $apiError, $setRequestLocale) {
        //     if (! $shouldReturnJson($request)) {
        //         return null;
        //     }

        //     $setRequestLocale($request);

        //     return $apiError(
        //         403,
        //         __('This action is unauthorized.'),
        //     );
        // });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($shouldReturnJson, $apiError, $setRequestLocale) {
            if (! $shouldReturnJson($request)) {
                return null;
            }

            $setRequestLocale($request);

            return $apiError(
                 429,
                $e->getMessage()
            );
        });

      

    // $exceptions->render(function (HttpResponseException $exception, Request $request) use ($shouldReturnJson, $apiError, $setRequestLocale) {
    //         if (! $shouldReturnJson($request)) {
    //             return null;
    //         }

    //         $setRequestLocale($request);

    //         if($exception->getPrevious() instanceof ValidationException){
    //             return $apiError(422, $exception->getPrevious()->validator->errors()->first());
    //         }
    //         return $apiError(300, 'just testing');
    //     });

        $exceptions->render(function (ValidationException $e, Request $request) use ($shouldReturnJson, $apiError, $setRequestLocale) {
            if (! $shouldReturnJson($request)) {
                return null;
            }

            $setRequestLocale($request);

            return $apiError(422, $e->validator->errors()->first());
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($shouldReturnJson, $apiError, $setRequestLocale) {
            if (! $shouldReturnJson($request)) {
                return null;
            }

            $setRequestLocale($request);
           

            return $apiError(404, __('Resource Not Found'));
        });

         $exceptions->render(function(AccessDeniedHttpException $e, Request $request) use ($shouldReturnJson, $apiError, $setRequestLocale){ 
            if (! $shouldReturnJson($request)) {
                return null;
            }

            $setRequestLocale($request);

    //         return response()->json([
    //     'status' => false,
    //     'message' => __('This action is unauthorized.'),
    // ], 403);
            return $apiError(403, __($e->getMessage()));

        });

          $exceptions->render(function (HttpException $e, Request $request) use ($shouldReturnJson, $apiError, $setRequestLocale) {
            if (! $shouldReturnJson($request)) {
                return null;
            }

            $setRequestLocale($request);

            return $apiError(
                $e->getStatusCode(),
                $e->getMessage() === ('CSRF token mismatch.') ? __('Session expired') : $e->getMessage()
            );
        });

        $exceptions->render(function (\Throwable $e, Request $request) use ($shouldReturnJson, $apiError, $setRequestLocale) {
            if (! $shouldReturnJson($request)) {
                return null;
            }

            $setRequestLocale($request);

            Log::error('Unhandled API exception', [
                'message' => $e->getMessage(),
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
            ]);

            return $apiError(
                222,
                __('Something went wrong, please try again later')
            );
        });
    })->create();
