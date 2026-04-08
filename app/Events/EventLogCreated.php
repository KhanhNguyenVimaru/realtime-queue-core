<?php

namespace App\Events;

use App\Models\EventLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventLogCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public EventLog $log;

    public function __construct(EventLog $log)
    {
        $this->log = $log;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('event.' . $this->log->event_id);
    }

    public function broadcastAs(): string
    {
        return 'event.log.created';
    }

    public function broadcastWith(): array
    {
        $user = $this->log->user;

        return [
            'id' => $this->log->id,
            'event_id' => $this->log->event_id,
            'action' => $this->log->action,
            'created_at' => optional($this->log->created_at)?->toIso8601String(),
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ] : null,
        ];
    }
}
