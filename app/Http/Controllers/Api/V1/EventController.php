<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\EventMessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function join(EventRequest $request)
    {
        $request = $request->validated();
        $event = \App\Models\Event::query()->find($request['id']);
        $event->participants()->syncWithoutDetaching(Auth::id());

        return returnSuccess([
            'joined' => true,
            'event_id' => $event->id,
            'channel' => "private-event.{$event->id}",
        ]);
    }

    public function send(EventRequest $request)
    {
        $request = $request->validated();
        $event = \App\Models\Event::query()->find($request['id']);
        $user = Auth::user();
        $payload = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'text' => $request['text'],
            'sent_at' => time(),
        ];

        broadcast(new EventMessageSent($event->id, $payload))->toOthers();

        return returnSuccess([
            'sent' => true,
            'message' => $payload,
        ]);
    }
}
