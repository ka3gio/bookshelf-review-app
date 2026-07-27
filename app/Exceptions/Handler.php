<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
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

        $this->renderable(function (Throwable $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($exception instanceof ValidationException) {
                return response()->json([
                    'message' => '入力内容に誤りがあります。',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $exception->errors(),
                ], $exception->status);
            }

            if ($exception instanceof AuthenticationException) {
                return $this->apiErrorResponse(
                    '認証が必要です。',
                    'AUTHENTICATION_REQUIRED',
                    401
                );
            }

            if ($exception instanceof HttpExceptionInterface) {
                return $this->httpExceptionResponse($exception);
            }

            return $this->apiErrorResponse(
                'サーバー内部でエラーが発生しました。',
                'INTERNAL_SERVER_ERROR',
                500
            );
        });
    }

    private function httpExceptionResponse(HttpExceptionInterface $exception): JsonResponse
    {
        $status = $exception->getStatusCode();

        [$message, $errorCode] = match ($status) {
            400 => ['リクエストが正しくありません。', 'BAD_REQUEST'],
            401 => ['認証が必要です。', 'AUTHENTICATION_REQUIRED'],
            403 => ['この操作を実行する権限がありません。', 'FORBIDDEN'],
            404 => ['指定されたリソースが見つかりません。', 'RESOURCE_NOT_FOUND'],
            405 => ['このHTTPメソッドは許可されていません。', 'METHOD_NOT_ALLOWED'],
            419 => ['セッションの有効期限が切れました。', 'CSRF_TOKEN_MISMATCH'],
            429 => ['リクエスト回数が上限を超えました。しばらくしてから再試行してください。', 'TOO_MANY_REQUESTS'],
            default => $status >= 500
                ? ['サーバー内部でエラーが発生しました。', 'INTERNAL_SERVER_ERROR']
                : ['リクエストの処理に失敗しました。', 'HTTP_ERROR'],
        };

        return $this->apiErrorResponse(
            $message,
            $errorCode,
            $status,
            $exception->getHeaders()
        );
    }

    /**
     * @param  array<string, string|string[]>  $headers
     */
    private function apiErrorResponse(
        string $message,
        string $errorCode,
        int $status,
        array $headers = []
    ): JsonResponse {
        return response()->json([
            'message' => $message,
            'error_code' => $errorCode,
        ], $status, $headers);
    }
}
