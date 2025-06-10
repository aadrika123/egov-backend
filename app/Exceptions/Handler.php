<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
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
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function unauthenticated($request, \Illuminate\Auth\AuthenticationException $exception)
    {
        Log::channel('apilogs')->error('🔐 Unauthenticated request', [
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'method' => $request->method(),
        ]);

        return response()->json([
            'status' => true,
            'authenticated' => false
        ], 200);
    }
}
