<?php

namespace App\Exceptions;

use App\Enums\ResponseCode;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * 不要被回填到 session 的敏感欄位
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * 例外記錄（非預期錯誤才寫 log）
     */
    public function report(Throwable $e): void
    {
        if (
            ! $e instanceof ValidationException &&
            ! $e instanceof HttpException &&
            ! $e instanceof ApiException
        ) {
            Log::error('Unhandled Exception', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        parent::report($e);
    }

    /**
     * 決定是否回傳 JSON
     */
    protected function shouldReturnJson($request, Throwable $e): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    /**
     * 統一錯誤輸出入口
     *
     * @throws Throwable
     */
    public function render($request, Throwable $e)
    {
        if (! $this->shouldReturnJson($request, $e)) {
            return parent::render($request, $e);
        }

        return $this->renderApiException($request, $e);
    }

    /**
     * API 錯誤處理核心
     */
    private function renderApiException($request, Throwable $exception)
    {
        /**
         *  業務錯誤（你在 code 裡 throw 的）
         */
        if ($exception instanceof ApiException) {
            $payload = [
                'code' => $exception->apiCode,
                'message' => $exception->getMessage(),
            ];

            if (! empty($exception->errors)) {
                $payload['errors'] = $exception->errors;
            }

            return response()->json($payload, $exception->httpStatus);
        }

        /**
         *  表單 / Request 驗證錯誤
         */
        if ($exception instanceof ValidationException) {
            return response()->json([
                'code' => ResponseCode::ValidateFailed,
                'message' => 'Validation Failed',
                'errors' => $exception->errors(),
            ], 422);
        }

        /**
         *  API 404
         */
        if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'code' => ResponseCode::NotFound,
                'message' => 'Resource Not Found',
            ], 404);
        }

        /**
         * HTTP 例外（403 / 401 / 429 / etc）
         */
        if ($exception instanceof HttpException) {
            return response()->json([
                'code' => ResponseCode::ErrorException,
                'message' => $exception->getMessage() ?: 'Request Error',
            ], $exception->getStatusCode());
        }

        /**
         *  未預期錯誤（500）
         */
        return response()->json([
            'code' => ResponseCode::ErrorException,
            'message' => config('app.debug')
                ? $exception->getMessage()
                : 'Server Error',
        ], 500);
    }
}
