<?php

namespace App\Http\Requests;

use App\Enums\ResponseCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class BaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 自訂驗證錯誤的回應格式
     * @throws ValidationException
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new ValidationException(
            $validator,
            response()->json([
                'code'   => ResponseCode::ValidateFailed,
                'errors' => $validator->errors(),
                'status' => 'validation_failed',
            ], 422),
        );
    }
}
