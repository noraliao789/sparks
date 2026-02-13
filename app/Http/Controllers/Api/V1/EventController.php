<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EventApplyStatus;
use App\Enums\RedisKey;
use App\Enums\RedisTtl;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Jobs\BroadcastEventMessage;
use App\Models\EventApply;
use App\Models\EventMessage;
use App\Services\EventApplyService;
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

    /**
     * @throws \Throwable
     */
    public function create(EventRequest $request, EventApplyService $service)
    {
        $request = $request->validated();
        $service->create($request);
        return returnSuccess();
    }

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
     * @throws \Throwable
     */
    public function approveApply(EventRequest $request, EventApplyService $service)
    {
        $data = $request->validated();
        $service->approve($data);

        return returnSuccess(['approved' => true]);
    }

    /**
     * @throws \Throwable
     */
    public function rejectApply(EventRequest $request, EventApplyService $service)
    {
        $data = $request->validated();
        $service->reject($data);

        return returnSuccess(['rejected' => true]);
    }

    public function my()
    {
        $uid = Auth::id();
        $events = \App\Models\Event::query()
            ->whereHas('invitedUsers', function ($query) use ($uid) {
                $query->where('event_users.user_id', $uid);
            })
            ->select(['id', 'theme_id', 'pay_id', 'title', 'description', 'start_time', 'end_time', 'num'])
            ->where('end_time', '>', time())
            ->orderBy('created_at', 'desc')
            ->get();

        return returnSuccess($events);
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
            'text' => $request['text'],
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
