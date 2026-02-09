<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventJoinController extends Controller
{
    /**
     * @throws ApiException
     */
    public function join(Request $request, Event $event)
    {
        if ($event->ends_at && now()->greaterThanOrEqualTo($event->ends_at)) {
            returnError(\App\Enums\ResponseCode::ValidateFailed, 'Event ended', 422);
        }

        $event->participants()->syncWithoutDetaching(Auth::id());

        return returnSuccess([
            'joined' => true,
            'event_id' => $event->id,
            'channel' => "private-event.{$event->id}", // 前端顯示
        ]);
    }
}
