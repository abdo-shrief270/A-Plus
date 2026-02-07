<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            // OTP Send Rate Limiter: 3 requests per minute per IP
            RateLimiter::for('otp-send', function (Request $request) {
                return Limit::perMinute(3)
                    ->by($request->ip())
                    ->response(function () {
                        return response()->json([
                            'status' => 429,
                            'message' => 'لقد تجاوزت الحد المسموح. يرجى الانتظار دقيقة قبل المحاولة مرة أخرى.',
                        ], 429);
                    });
            });

            // OTP Verify Rate Limiter: 5 attempts per minute per IP
            RateLimiter::for('otp-verify', function (Request $request) {
                return Limit::perMinute(5)
                    ->by($request->ip())
                    ->response(function () {
                        return response()->json([
                            'status' => 429,
                            'message' => 'لقد تجاوزت عدد المحاولات المسموحة. يرجى الانتظار دقيقة.',
                        ], 429);
                    });
            });

            // Login Rate Limiter: 5 attempts per minute per IP
            RateLimiter::for('login', function (Request $request) {
                return Limit::perMinute(5)
                    ->by($request->ip())
                    ->response(function () {
                        return response()->json([
                            'status' => 429,
                            'message' => 'لقد تجاوزت عدد محاولات تسجيل الدخول. يرجى الانتظار دقيقة.',
                        ], 429);
                    });
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'jwt' => \App\Http\Middleware\JwtMiddleware::class
        ]);
    })
    ->withExceptions(function ($exceptions) {
        $exceptions->shouldRenderJsonWhen(function (\Illuminate\Http\Request $request, Throwable $e) {
            // Force JSON response for API routes
            return $request->is('api/*') || $request->expectsJson();
        });
        $exceptions->render(function (Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'status' => 422,
                        'message' => 'Validation failed',
                        'errors' => $e->errors(),
                    ]);
                }

                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'status' => 401,
                        'message' => 'Unauthenticated',
                    ]);
                }

                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'Resource not found',
                    ]);
                }

                // Default exception handler
                return response()->json([
                    'status' => method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500,
                    'message' => $e->getMessage(),
                ]);
            }

        });
    })
    ->create();
