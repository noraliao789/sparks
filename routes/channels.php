<?php

use App\Models\Event;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('event.{eventId}', static function ($user, $eventId) {
    \Log::info('Channel auth check', [
        'user_id' => $user?->id,
        'event_id' => $eventId,
    ]);
    $event = Event::query()->find($eventId);
    if (!$event) {
        return false;
    }

    // 活動結束 => 立刻不能進聊天室
    if ($event->ends_at && now()->greaterThanOrEqualTo($event->ends_at)) {
        return false;
    }

    // 必須是活動成員才能進
    return $event->participants()->where('users.id', $user->id)->exists();
});
