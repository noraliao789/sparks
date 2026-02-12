<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Controller;
use App\Models\EventUser;
use Illuminate\Support\Facades\Auth;

class MeController extends Controller
{
    public function channels()
    {
        $userId = Auth::id();

        $eventIds = EventUser::query()
            ->whereHas('events', function ($query) {
                $query->where('events.end_time', '>', time());
            })
            ->where('event_users.user_id', $userId)
            ->pluck('event_users.event_id')
            ->toArray();
        $eventChannels = array_map(
            fn($id) => "private-event.{$userId}",
            $eventIds
        );

        return returnSuccess([
            'user_channel' => "private-user.{$userId}",
            'event_channels' => $eventChannels,
        ]);
    }
}