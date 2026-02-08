<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ResponseCode extends Enum
{
    /** 成功 */
    const int Success = 20000;

    /** 驗證失敗 */
    const int ValidateFailed = 42201;

    /** 未授權（未登入 / Token 無效） */
    const int      Unauthorized = 40101;

    const int      TokenRequired = 40102;

    const int      TokenNotFound = 40103;

    const int      TokenInvalid = 40104;

    const int      TokenExpired = 40105;

    /** 權限不足 */
    const int Forbidden = 40301;

    /** 資源不存在 */
    const int NotFound = 40401;

    /** 請求衝突（重複資料等） */
    const int Conflict = 40901;

    /** 請求錯誤（參數或流程錯誤） */
    const int BadRequest = 40001;

    /** 系統例外 / 未預期錯誤 */
    const int ErrorException = 50000;

    /** 第三方服務錯誤 */
    const int ThirdPartyServiceError = 50201;
}
