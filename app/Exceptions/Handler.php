<?php

namespace App\Exceptions;

use App\Models\Book;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
     * 未認証時のレスポンスを返す。
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->is('api/*')) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => '認証が必要です。',
            ], 401);
        }

        return parent::unauthenticated($request, $exception);
    }

    /**
     * APIバリデーションエラー時のレスポンスを返す。
     */
    protected function invalidJson($request, ValidationException $exception)
    {
        return response()->json([
            'error' => 'Validation Error',
            'message' => '入力内容に誤りがあります。',
            'errors' => $exception->errors(),
        ], $exception->status);
    }

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (AccessDeniedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'この操作を行う権限がありません。',
                ], 403);
            }
        });

        $this->renderable(function (NotFoundHttpException $e, $request) {
            $previous = $e->getPrevious();

            if (
                $request->is('api/v1/books/*')
                && $previous instanceof ModelNotFoundException
                && $previous->getModel() === Book::class
            ) {
                return response()->json([
                    'error' => 'Not Found',
                    'message' => '書籍が見つかりませんでした。',
                ], 404);
            }
        });
    }
}
