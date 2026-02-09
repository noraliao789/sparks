<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\EventMessageSent;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventChatController extends Controller
{
    /**
     * @throws ApiException
     */
    public function send(Request $request, Event $event)
    {
        $user = Auth::user();
        if ($user) {
            returnError(\App\Enums\ResponseCode::Unauthorized, 'Unauthorized', 401);
        }
        // 必須是成員
        if (! $event->participants()->where('users.id', $user->id)->exists()) {
            returnError(\App\Enums\ResponseCode::Forbidden, 'Not a participant', 403);
        }

        // 活動結束 => 禁止聊天
        if ($event->ends_at && now()->greaterThanOrEqualTo($event->ends_at)) {
            returnError(\App\Enums\ResponseCode::ValidateFailed, 'Event ended', 422);
        }

        $data = $request->validate([
            'text' => ['required', 'string', 'max:1000'],
        ]);

        $payload = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'text' => $data['text'],
            'sent_at' => time(),
        ];

        broadcast(new EventMessageSent($event->id, $payload))->toOthers();

        return returnSuccess([
            'sent' => true,
            'message' => $payload,
        ]);
    }
}
