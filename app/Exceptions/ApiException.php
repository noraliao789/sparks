<?php

namespace App\Exceptions;

use App\Enums\ResponseCode;
use Exception;

class ApiException extends Exception
{
    public readonly int $httpStatus;

    public readonly ResponseCode $apiCode;

    public readonly array $errors;

    public function __construct(
        ResponseCode|int $apiCode = ResponseCode::ErrorException,
        string $message = '',
        int $httpStatus = 400,
        array $errors = []
    ) {
        $this->apiCode = $apiCode;
        $this->httpStatus = $httpStatus;
        $this->errors = $errors;

        parent::__construct($message, $apiCode->value);
    }
}
