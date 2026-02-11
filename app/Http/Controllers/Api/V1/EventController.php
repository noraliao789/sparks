<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RedisKey;
use App\Enums\RedisTtl;
use App\Enums\EventApplyStatus;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Jobs\BroadcastEventMessage;
use App\Models\EventMessage;
use App\Models\EventApply;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class EventController extends Controller
{
    public function index(EventRequest $request)
    {
        $request = $request->validated();
        $events = \App\Models\Event::query()
            ->select(['id', 'theme_id', 'pay_id', 'title', 'description', 'start_time', 'end_time', 'num'])
            ->where('end_time', '>', time())
            ->orderBy('created_at', 'desc')
            ->paginate($request['limit'], ['*'], 'page', $request['page']);
        return returnSuccess($events);
    }

    public function create(EventRequest $request)
    {
        $request = $request->validated();
        $uid = Auth::id();
        $event = \App\Models\Event::query()->create([
            'theme_id' => $request['theme_id'],
            'pay_id' => $request['pay_id'],
            'title' => $request['title'],
            'description' => $request['description'] ?? null,
            'start_time' => $request['start_time'],
            'end_time' => $request['end_time'],
            'num' => $request['num'],
            'creator_by' => $uid,
            'created_at' => time(),
        ]);
        $event->participants()->attach($uid);
        $key = RedisKey::EVENT_PARTICIPANTS . $event->id;
        Redis::sadd($key, (string)$uid);
        Redis::expire($key, RedisTtl::EVENT_PARTICIPANTS);
        return returnSuccess();
    }

    /**
     * @throws ApiException
     */
    public function apply(EventRequest $request)
    {
        $data = $request->validated();
        $uid = Auth::id();
        $event = \App\Models\Event::query()->find($data['id']);

        EventApply::query()->create([
            'event_id' => $event->id,
            'user_id' => $uid,
            'status' => EventApplyStatus::PENDING,
            'message' => $data['message'] ?? null,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        return returnSuccess();
    }

    /**
     * @throws ApiException
     */
    public function join(EventRequest $request)
    {
        $request = $request->validated();
        $uid = Auth::id();
        $event = \App\Models\Event::query()->find($request['id']);
        $event->participants()->syncWithoutDetaching($uid);
        $key = RedisKey::EVENT_PARTICIPANTS . $event->id;
        Redis::sadd($key, (string)$uid);
        Redis::expire($key, RedisTtl::EVENT_PARTICIPANTS);
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
            'text' => json_encode($request['text']),
            'created_at' => time(),
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
