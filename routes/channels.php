<?php

use App\Enums\RedisKey;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('event.{eventId}', function ($user, $eventId) {
    return \Illuminate\Support\Facades\Redis::sismember(
        RedisKey::EVENT_PARTICIPANTS . $eventId,
        (string)$user->id
    );
});
