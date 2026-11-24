<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
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
	public function render($request, Throwable $e)
    {
        if ($e instanceof \Illuminate\Session\TokenMismatchException) {
            if ($request->expectsJson() || $request->ajax() || $request->is('admin/loginApi') || $request->is('api/*')) {
                return response()->json([
                    'status' => 419,
                    'error' => ['Your session has expired. Please refresh the page and try again.'],
                    'message' => 'Your session has expired. Please refresh the page and try again.'
                ], 419);
            }
            return redirect('/admin/login')->with('error', 'Your session has expired. Please log in again.');
        }

        if ($e instanceof \Illuminate\Contracts\Encryption\DecryptException) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['status' => 401, 'message' => 'Session expired. Please log in again.'], 401);
            }
            return redirect('/login')->withCookie(\Illuminate\Support\Facades\Cookie::forget(config('session.cookie')));
        }
        if ($this->isHttpException($e)) {
            $status = (int) $e->getStatusCode();
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => $status,
                    'message' => $e->getMessage() ?: ($status === 404 ? 'Resource not found.' : 'HTTP Error ' . $status),
                ], $status);
            }

            $layout = $request->is('admin/*') ? 'side-menu' : 'login';
            switch ($status) {
                case 403:
                    return response()->view('pages.404', ['layout' => $layout], 403);
                case 404:
                    return response()->view('pages.404', ['layout' => $layout], 404);
                case 500:
                    return response()->view('pages.500', ['layout' => $layout], 500);
                default:
                    return $this->renderHttpException($e);
            }
        } else {
            if (!env('APP_DEBUG', false)) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json(['status' => 500, 'message' => 'Internal server error.'], 500);
                }
                return response()->view("pages.500", ['layout' => 'side-menu'], 500);
            } else {
                return parent::render($request, $e);
            }
        }
    }
}
