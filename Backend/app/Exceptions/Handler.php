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
        if ($e instanceof \Illuminate\Contracts\Encryption\DecryptException) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['status' => 401, 'message' => 'Session expired. Please log in again.'], 401);
            }
            return redirect('/login')->withCookie(\Illuminate\Support\Facades\Cookie::forget(config('session.cookie')));
        }
        if ($this->isHttpException($e)) {
            switch ($e->getStatusCode()) {

                // not authorized
                case '403':
                    return redirect('404');
                    break;

                // not found
                case '404':
                    return redirect('404');
                    break;

                // internal error
                case '500':
                    return redirect('404');
                    break;

                default:
                    return $this->renderHttpException($e);
                    break;
            }
        } else {
            if (!env('APP_DEBUG', false)) {
                return response()->view("pages.500");
            } else {
                return parent::render($request, $e);
            }
            // return parent::render($request, $e);
        }
    }
}
