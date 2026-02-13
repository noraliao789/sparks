<?php

namespace App\Http\Requests;

use App\Enums\EventApplyStatus;
use App\Enums\ResponseCode;
use App\Exceptions\ApiException;
use App\Models\EventApply;
use Illuminate\Support\Facades\Auth;

class EventRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     *
     * @throws ApiException
     */
    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            'index' => [
                'page' => 'integer|min:1',
                'limit' => 'integer|min:1|max:100',
            ],
            'create' => [
                'theme_id' => 'required|integer',
                'pay_id' => 'required|integer',
                'title' => 'required|string|max:255',
                'description' => 'string|max:1000',
                'start_time' => 'required|integer|gt:0',
                'end_time' => 'required|integer|gt:start_time',
                'num' => 'required|integer|min:1',
            ],
            'apply' => [
                'id' => 'required|exists:events,id',
                'message' => 'required|string|max:50',
                'unlock_photo' => 'required|in:0,1',
            ],
            'approveApply' => [
                'apply_id' => 'required|exists:event_applies,id',
                'id' => 'required|exists:events,id',
            ],
            'rejectApply' => [
                'apply_id' => 'required|exists:event_applies,id',
                'id' => 'required|exists:events,id',
                'reason' => 'string|max:50',
            ],
            'send' => [
                'id' => 'required|exists:events,id',
                'text' => 'required|array',
            ],
            'join' => [
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
        $user = Auth::user();
        if (!$user) {
            returnError(\App\Enums\ResponseCode::Unauthorized, 'Unauthorized', 401);
        }
        if (!in_array($method, ['index', 'create'])) {
            $event = \App\Models\Event::query()->find($id);
            if (!$event) {
                returnError(\App\Enums\ResponseCode::NotFound, 'Event not found', 404);
            }

            if ($method === 'join') {
                if ($event->ends_at && now()->greaterThanOrEqualTo($event->ends_at)) {
                    returnError(\App\Enums\ResponseCode::ValidateFailed, 'Event ended', 422);
                }
                // 私密活動只能邀請的用戶加入
                if (!$event->invitedUsers()->where('users.id', $user->id)->exists()) {
                    returnError(\App\Enums\ResponseCode::Forbidden, 'Not invited', 403);
                }
                $ok = \App\Models\EventApply::query()
                    ->where('event_id', $id)
                    ->where('user_id', $user->id)
                    ->where('status', EventApplyStatus::APPROVED)
                    ->exists();

                if (!$ok) {
                    returnError(\App\Enums\ResponseCode::Forbidden, 'Not approved', 403);
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
            if ($method === 'apply') {
                // 活動已結束不可報名
                if ((int)$event->end_time <= time()) {
                    returnError(\App\Enums\ResponseCode::EventEnded, 'Event ended', 422);
                }
                // 防重複報名
                $exists = EventApply::query()
                    ->where('event_id', $id)
                    ->where('user_id', $user->id)
                    ->first();
                if ($exists) {
                    returnError(\App\Enums\ResponseCode::EventAlreadyApplied, 'Already applied', 422);
                }
                $count = EventApply::query()
                    ->where('event_id', $event->id)
                    ->where('status', EventApplyStatus::APPROVED)
                    ->count();
                if ($event->num && $count >= (int)$event->num) {
                    returnError(\App\Enums\ResponseCode::EventApplyIsFull, 'Event is full', 422);
                }
            }
            if ($method === 'approveApply') {
                if ($event->creator_by !== $user->id) {
                    returnError(\App\Enums\ResponseCode::Forbidden, 'Forbidden', 403);
                }

                if ($event->end_time <= time()) {
                    returnError(\App\Enums\ResponseCode::EventEnded, 'Event ended', 422);
                }
                $applyId = $this->input('apply_id');
                $apply = EventApply::query()
                    ->where('id', $applyId)
                    ->where('event_id', $id)
                    ->lockForUpdate()
                    ->first();

                if (!$apply) {
                    returnError(\App\Enums\ResponseCode::NotFound, 'Apply not found', 404);
                }

                if ($apply->status !== (int)EventApplyStatus::PENDING) {
                    returnError(\App\Enums\ResponseCode::EventApplyStatusInvalid, 'Apply status invalid', 422);
                }
            }
            if ($method === 'rejectApply') {
                if ((int)$event->creator_by !== $user->id) {
                    returnError(\App\Enums\ResponseCode::Forbidden, 'Forbidden', 403);
                }
                $applyId = $this->input('apply_id');

                $apply = EventApply::query()
                    ->where('id', $applyId)
                    ->where('event_id', $id)
                    ->lockForUpdate()
                    ->first();

                if (!$apply) {
                    returnError(\App\Enums\ResponseCode::NotFound, 'Apply not found', 404);
                }

                if ((int)$apply->status !== (int)EventApplyStatus::PENDING) {
                    returnError(\App\Enums\ResponseCode::EventApplyStatusInvalid, 'Apply status invalid', 422);
                }
            }
        }

        return true;
    }
}
