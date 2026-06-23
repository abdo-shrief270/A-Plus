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

            // Registration: account creation is rare per person, and each new
            // student is auto-granted a free trial — throttle hard to stop
            // mass account / trial farming from one IP.
            RateLimiter::for('register', function (Request $request) {
                return Limit::perHour(15)
                    ->by($request->ip())
                    ->response(fn () => response()->json([
                        'status' => 429,
                        'message' => 'محاولات إنشاء حسابات كثيرة. يرجى المحاولة لاحقاً.',
                    ], 429));
            });

            // Username/login existence probes — limit enumeration of accounts.
            RateLimiter::for('auth-probe', function (Request $request) {
                return Limit::perMinute(20)
                    ->by($request->ip())
                    ->response(fn () => response()->json([
                        'status' => 429,
                        'message' => 'طلبات كثيرة جداً. يرجى الانتظار قليلاً.',
                    ], 429));
            });

            // Password reset/change — sensitive; keep tight per IP.
            RateLimiter::for('password-reset', function (Request $request) {
                return Limit::perMinute(5)
                    ->by($request->ip())
                    ->response(fn () => response()->json([
                        'status' => 429,
                        'message' => 'محاولات كثيرة. يرجى الانتظار دقيقة قبل المحاولة مرة أخرى.',
                    ], 429));
            });

            // Public contact form — anti-spam.
            RateLimiter::for('contact', function (Request $request) {
                return Limit::perMinute(5)
                    ->by($request->ip())
                    ->response(fn () => response()->json([
                        'status' => 429,
                        'message' => 'لقد أرسلت رسائل كثيرة. يرجى المحاولة لاحقاً.',
                    ], 429));
            });

            // Quiz reads (pool-count, show, history, leaderboard, review): generous
            RateLimiter::for('quiz-read', function (Request $request) {
                return Limit::perMinute(120)
                    ->by(optional(auth('api')->user())->id ?: $request->ip())
                    ->response(fn () => response()->json([
                        'status' => 429,
                        'message' => 'طلبات كثيرة جداً. يرجى الانتظار قليلاً.',
                    ], 429));
            });

            // Quiz answer submissions: ~1/second sustained is plenty for a human
            RateLimiter::for('quiz-answer', function (Request $request) {
                return Limit::perMinute(60)
                    ->by(optional(auth('api')->user())->id ?: $request->ip())
                    ->response(fn () => response()->json([
                        'status' => 429,
                        'message' => 'إجابات متسارعة بشكل غير طبيعي. يرجى التمهّل.',
                    ], 429));
            });

            // Session lifecycle (create/complete/abandon/daily start): rare actions
            RateLimiter::for('quiz-mutate', function (Request $request) {
                return Limit::perMinute(15)
                    ->by(optional(auth('api')->user())->id ?: $request->ip())
                    ->response(fn () => response()->json([
                        'status' => 429,
                        'message' => 'طلبات كثيرة جداً. يرجى الانتظار قليلاً.',
                    ], 429));
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'jwt' => \App\Http\Middleware\JwtMiddleware::class,
            'single-device' => \App\Http\Middleware\EnforceSingleDevice::class,
        ]);
    })
    ->withExceptions(function ($exceptions) {
        $exceptions->shouldRenderJsonWhen(function (\Illuminate\Http\Request $request, Throwable $e) {
            // Force JSON response for API routes
            return $request->is('api/*') || $request->expectsJson();
        });
        $exceptions->render(function (Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                // Some framework exceptions carry their own ready response
                // (rate-limiter custom 429s, redirects). Return it verbatim —
                // mangling these turned throttle hits into empty 500s.
                if ($e instanceof \Illuminate\Http\Exceptions\HttpResponseException) {
                    return $e->getResponse();
                }

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

                // Default exception handler. HttpExceptions carry intentional,
                // user-facing messages; anything else (DB errors, runtime
                // failures) must not leak internals — SQL, paths, stack info —
                // to API clients outside of debug mode.
                $isHttp = method_exists($e, 'getStatusCode');
                $status = $isHttp ? $e->getStatusCode() : 500;

                return response()->json([
                    'status' => $status,
                    'message' => ($isHttp || config('app.debug'))
                        ? $e->getMessage()
                        : 'حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.',
                ], $status);
            }

        });
    })
    ->create();
