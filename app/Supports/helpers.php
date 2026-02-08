<?php

use App\Enums\ResponseCode;
use App\Exceptions\ApiException;

if (! function_exists('returnError')) {
    /**
     * @throws ApiException
     */
    function returnError(
        ResponseCode|int $code = ResponseCode::ErrorException,
        string $message = '',
        int $statusCode = 400,
        array $errors = []
    ): never {
        throw new ApiException($code, $message, $statusCode, $errors);
    }
}

if (! function_exists('returnSuccess')) {
    function returnSuccess($params = [], $headers = []): \Illuminate\Http\JsonResponse
    {
        $data = ['code' => ResponseCode::Success];
        if (! empty($params)) {
            $data['data'] = $params;
        }

        return response()->json(
            $data,
            200,
            array_merge([
                'Content-Type' => 'application/json;charset=UTF-8',
                'Charset' => 'utf-8',
            ], $headers),
            JSON_UNESCAPED_UNICODE,
        );
    }
}
if (! function_exists('get_user_agent')) {
    /**
     * 獲取使用者代理類型（app 或 web）
     *
     * 請求 header 中讀取 user_agent（或 User-Agent），
     * 若包含常見行動設備關鍵字則回傳 'app'，否則回傳 'web'。
     */
    function get_user_agent(): string
    {
        $ua = '';
        if (function_exists('request') && function_exists('app') && app()->bound('request')) {
            $ua = request()->header('user_agent') ?? request()->header('User-Agent') ?? '';
        }

        if (empty($ua)) {
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }
        $uaLower = strtolower($ua);
        $mobileTokens = [
            'android', 'ios',
        ];

        if (array_any($mobileTokens, fn ($token) => $token !== '' && str_contains($uaLower, $token))) {
            return 'app';
        }

        return 'web';
    }
}
