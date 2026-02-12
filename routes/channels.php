<?php

use App\Enums\RedisKey;
use App\Enums\RedisTtl;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Redis;

Broadcast::channel('event.{eventId}', function ($user, $eventId) {
    $cacheKey = RedisKey::EVENT_BASIC . $eventId;

    $event = Cache::remember($cacheKey, 600, function () use ($eventId) {
        return Event::query()->select(['id', 'title', 'start_time', 'num', 'end_time'])->find($eventId);
    });

    if (!$event) {
        return false;
    }
    if ((int)$event->end_time <= time()) {
        return false;
    }

    $key = RedisKey::EVENT_PARTICIPANTS . $eventId;

    if (Redis::exists($key)) {
        return Redis::sismember($key, (string)$user->id);
    }

    $exists = Event::query()
        ->whereKey((int)$eventId)
        ->whereHas('participants', function ($q) use ($user) {
            $q->where('users.id', (int)$user->id);
        })
        ->exists();

    if ($exists) {
        $ids = \DB::table('event_users')
            ->where('event_id', (int)$eventId)
            ->pluck('user_id')
            ->map(fn($id) => (string)$id)
            ->toArray();

        if (!empty($ids)) {
            Redis::sadd($key, ...$ids);
            Redis::expire($key, RedisTtl::EVENT_PARTICIPANTS);
        }
    }

    return $exists;
});
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int)$user->id === (int)$userId;
});