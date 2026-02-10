<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('event.{eventId}', function ($user, $eventId) {
    return \Illuminate\Support\Facades\Redis::sismember(
        "event:participants:$eventId",
        (string)$user->id
    );
});
