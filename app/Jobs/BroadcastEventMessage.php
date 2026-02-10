<?php

namespace App\Jobs;

use App\Events\EventMessageSent;
use App\Models\EventMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BroadcastEventMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $messageId) {}

    public function handle(): void
    {
        $message = EventMessage::with('user')->find($this->messageId);
        if (!$message) {
            return;
        }

        $payload = [
            'id' => $message->id,
            'text' => $message->text,
            'user' => [
                'id' => $message->user->id,
                'name' => $message->user->name,
            ],
            'sent_at' => $message->created_at,
        ];

        broadcast(new EventMessageSent($message->event_id, $payload));
    }
}
