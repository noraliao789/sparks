<?php

namespace App\Http\Requests;

use App\Enums\ResponseCode;
use App\Exceptions\ApiException;
use Illuminate\Foundation\Http\FormRequest;

class ActivityRequest extends BaseRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     * @throws ApiException
     */
    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            "index" => [
                'page' => 'integer|min:1',
                'limit' => 'integer|min:1',
                'theme_id' => 'integer',
                'title' => 'string',
            ],
            "create" => [
                'title' => 'required|string|max:200',
                'theme_id' => 'required|integer',
                'starts_at' => 'required|date',
                'ends_at' => 'required|date|after:starts_at',
                'cover' => 'string|max:500',
                'description' => 'string|max:500',
            ],
            default => returnError(ResponseCode::RequestValidationNotSet, '', 403),
        };
    }
}
