<?php

namespace App\Http\Requests;

use App\Enums\ResponseCode;
use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Auth;

class EventRequest extends BaseRequest
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
            "send" => [
                'id' => 'required|exists:events,id',
                'text' => 'required|array',
            ],
            "join" => [
                'id' => 'required|exists:events,id',
            ],
            default => returnError(ResponseCode::RequestValidationNotSet, '', 403),
        };
    }

    /**
     * @throws ApiException
     */
    public function authorize(): bool
    {
        $method = $this->route()->getActionMethod();
        $id = $this->input('id');
        $event = \App\Models\Event::query()->find($id);
        $user = Auth::user();
        if (!$user) {
            returnError(\App\Enums\ResponseCode::Unauthorized, 'Unauthorized', 401);
        }
        if ($method === 'join') {
            if ($event->ends_at && now()->greaterThanOrEqualTo($event->ends_at)) {
                returnError(\App\Enums\ResponseCode::ValidateFailed, 'Event ended', 422);
            }
            // 私密活動只能邀請的用戶加入
            if (!$event->invitedUsers()->where('users.id', $user->id)->exists()) {
                returnError(\App\Enums\ResponseCode::Forbidden, 'Not invited', 403);
            }
        }
        if ($method === 'send') {
            // 必須是成員
            if (!$event->participants()->where('users.id', $user->id)->exists()) {
                returnError(\App\Enums\ResponseCode::Forbidden, 'Not a participant', 403);
            }

            // 活動結束 => 禁止聊天
            if ($event->ends_at && now()->greaterThanOrEqualTo($event->ends_at)) {
                returnError(\App\Enums\ResponseCode::ValidateFailed, 'Event ended', 422);
            }
        }
        return true;
    }
}
