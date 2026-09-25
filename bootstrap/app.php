<?php

use App\Http\Middleware\AuthenticateMobileApiToken;
use App\Http\Middleware\CheckMobileRoleRedirect;
use App\Http\Middleware\EnsureUserAssigned;
use App\Http\Middleware\LogMobileApiRequest;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\RequireActiveRole;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrackUserOnlineStatus;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    /*
        ->withRouting(
            web: __DIR__.'/../routes/web.php',
            commands: __DIR__.'/../routes/console.php',
            health: '/up',
        )
        ->withMiddleware(function (Middleware $middleware): void {
            //
        })
        ->withExceptions(function (Exceptions $exceptions): void {

            $exceptions->shouldRenderJsonWhen(
                fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
            );
        })->create();
    */
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            require __DIR__.'/../routes/mobile.php';
        },
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('orders:auto-cancel-overdue')->dailyAt('00:05');
        $schedule->command('inventory:reconcile-reservations')->dailyAt('00:10');
        $schedule->command('order-drafts:process-automation')->dailyAt('00:13');
        $schedule->command('order-schedules:process-daily-rules')->dailyAt('00:14');
        $schedule->command('order-schedules:evaluate-today')->dailyAt('00:15');
        $schedule->command('customers:apply-free-reset')->dailyAt('00:20');
    })

    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            TrackUserOnlineStatus::class,
            SetLocale::class,
        ]);

        $middleware->alias([
            'permission' => PermissionMiddleware::class,
            'setLocale' => SetLocale::class,
            'role' => RoleMiddleware::class,
            'active.role' => RequireActiveRole::class,
            'mobile.api' => AuthenticateMobileApiToken::class,
            'mobile.api.log' => LogMobileApiRequest::class,
            'mobile.role.redirect' => CheckMobileRoleRedirect::class,
            'assigned' => EnsureUserAssigned::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $exception, Request $request) {
            $isPageExpired = $exception instanceof TokenMismatchException
                || ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() === 419);

            if ($isPageExpired) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.',
                    ], 419);
                }

                return redirect()
                    ->route('login')
                    ->with('error', 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.');
            }

            $isForbidden = $exception instanceof AuthorizationException
                || ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() === 403);

            if (! $isForbidden || $request->expectsJson() || $request->is('api/*')) {
                return null;
            }

            if (! $request->user()) {
                return redirect()->route('login');
            }

            $message = trim((string) $exception->getMessage());

            return redirect()
                ->route('home')
                ->with('error', $message !== '' ? $message : 'Bạn không có quyền truy cập chức năng này.');
        });
    })->create();
