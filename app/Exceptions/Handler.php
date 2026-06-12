<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof TokenMismatchException) {
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

        if ($isForbidden) {
            $message = trim((string) $exception->getMessage());
            if ($message === '' || str_contains(strtolower($message), 'unauthorized')) {
                $message = 'Bạn không có quyền truy cập chức năng này.';
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => $message,
                ], 403);
            }

            return redirect()
                ->route('home')
                ->with('error', $message);
        }

        // Nếu không phải lỗi 403, Laravel sẽ xử lý các lỗi khác như bình thường
        return parent::render($request, $exception);
    }
}
