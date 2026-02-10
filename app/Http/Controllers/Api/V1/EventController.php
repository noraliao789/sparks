<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Jobs\BroadcastEventMessage;
use App\Models\EventMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class EventController extends Controller
{
    /**
     * @throws ApiException
     */
    public function join(EventRequest $request)
    {
        $request = $request->validated();

        $uid = Auth::id();
        $event = \App\Models\Event::query()->find($request['id']);
        if (! $event) {
            returnError();
        }
        $event->participants()->syncWithoutDetaching($uid);

        Redis::sadd("event:participants:{$event->id}", (string) $uid);
        Redis::expire("event:participants:{$event->id}", 60 * 60 * 24);

        return returnSuccess([
            'joined' => true,
            'event_id' => $event->id,
            'channel' => "private-event.{$event->id}",
        ]);
    }

    public function send(EventRequest $request)
    {
        $request = $request->validated();
        $user = Auth::user();

        $message = EventMessage::create([
            'event_id' => $request['id'],
            'user_id' => $user->id,
            'text' => $request['text'],
        ]);
        BroadcastEventMessage::dispatch($message->id)->onQueue('broadcast');

        return returnSuccess([
            'sent' => true,
            'message' => [
                'id' => $message->id,
                'text' => $message->text,
                'sent_at' => $message->created_at,
            ],
        ]);
    }
}
