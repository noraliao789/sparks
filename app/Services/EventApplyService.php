<?php

namespace App\Services;

use App\Enums\EventApplyStatus;
use App\Enums\RedisKey;
use App\Enums\RedisTtl;
use App\Events\UserNotificationCreated;
use App\Models\Event;
use App\Models\EventApply;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class EventApplyService
{
    /**
     * @throws \Throwable
     */
    public function approve(array $data): void
    {
        DB::transaction(function () use ($data) {
            $applyId = $data['apply_id'];
            $eventId = $data['id'];

            $apply = EventApply::query()
                ->where('id', $applyId)
                ->where('event_id', $eventId)
                ->lockForUpdate()
                ->first();


            $apply->status = EventApplyStatus::APPROVED;
            $apply->updated_at = time();
            $apply->save();

            $targetUserId = (int)$apply->user_id;
            $event = Event::query()->find($eventId);
            $event->participants()->syncWithoutDetaching($targetUserId);

            $key = RedisKey::EVENT_PARTICIPANTS . $event->id;
            Redis::sadd($key, (string)$targetUserId);
            Redis::expire($key, RedisTtl::EVENT_PARTICIPANTS);

            broadcast(
                new UserNotificationCreated(
                    userId: $targetUserId,
                    payload: [
                        'type' => 'event.apply.approved',
                        'event_id' => (int)$event->id,
                        'title' => (string)$event->title,
                        'at' => time(),
                    ]
                )
            );
        });
    }

    /**
     * @throws \Throwable
     */
    public function reject(array $data): void
    {
        DB::transaction(function () use ($data) {
            $applyId = $data['apply_id'];
            $eventId = $data['id'];
            $actorUserId = Auth::id();
            $event = Event::query()->find($eventId);
            if (!$event) {
                returnError(\App\Enums\ResponseCode::NotFound, 'Event not found', 404);
            }

            if ((int)$event->creator_by !== (int)$actorUserId) {
                returnError(\App\Enums\ResponseCode::Forbidden, 'Forbidden', 403);
            }

            $apply = EventApply::query()
                ->where('id', $applyId)
                ->where('event_id', $eventId)
                ->lockForUpdate()
                ->first();

            if (!$apply) {
                returnError(\App\Enums\ResponseCode::NotFound, 'Apply not found', 404);
            }

            if ((int)$apply->status !== (int)EventApplyStatus::PENDING) {
                returnError(\App\Enums\ResponseCode::EventApplyStatusInvalid, 'Apply status invalid', 422);
            }

            $apply->reason = $data['reason'] ?? null;
            $apply->status = EventApplyStatus::REJECTED;
            $apply->updated_at = time();
            $apply->save();

            $targetUserId = (int)$apply->user_id;

            broadcast(
                new UserNotificationCreated(
                    userId: $targetUserId,
                    payload: [
                        'type' => 'event.apply.rejected',
                        'event_id' => (int)$event->id,
                        'title' => (string)$event->title,
                        'reason' => $data['reason'] ?? null,
                        'at' => time(),
                    ]
                )
            );
        });
    }
}
