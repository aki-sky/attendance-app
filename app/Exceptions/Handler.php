<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
     * APIの例外をJSON形式で返す。
     */
    public function render($request, Throwable $exception)
    {
        if ($request->is('api/*')) {
            if (
                $exception instanceof ModelNotFoundException
                || $exception instanceof NotFoundHttpException
            ) {
                return response()->json([
                    'error' => '勤怠情報が見つかりませんでした。',
                ], 404);
            }

            if ($exception instanceof AuthorizationException) {
                return response()->json([
                    'error' => 'この操作を実行する権限がありません。',
                ], 403);
            }
        }

        return parent::render($request, $exception);
    }
}
