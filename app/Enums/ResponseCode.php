<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ResponseCode extends Enum
{
    const int Success = 20000;
    const int ValidateFailed = 42201;
    const int RequestValidationNotSet = 42202;

    const int      Unauthorized = 40101;
    const int      TokenRequired = 40102;
    const int      TokenNotFound = 40103;
    const int      TokenInvalid = 40104;
    const int      TokenExpired = 40105;
    const int Forbidden = 40301;
    const int NotFound = 40401;
    const int Conflict = 40901;
    const int BadRequest = 40001;
    const int ErrorException = 50000;
    const int ThirdPartyServiceError = 50201;

    public static function getDescription($value): string
    {
        return match ($value) {
            self::Success => '成功',
            self::ValidateFailed => '驗證失敗',
            self::RequestValidationNotSet => '請求驗證未設定',
            self::Unauthorized => '未授權',
            self::TokenRequired => 'Token 是必需的',
            self::TokenNotFound => '找不到 Token',
            self::TokenInvalid => 'Token 無效',
            self::TokenExpired => 'Token 已過期',
            self::Forbidden => '權限不足',
            self::NotFound => '資源不存在',
            self::Conflict => '請求衝突',
            self::BadRequest => '請求錯誤',
            self::ErrorException => '系統例外',
            self::ThirdPartyServiceError => '第三方服務錯誤',
            default => parent::getDescription($value),
        };
    }
}
