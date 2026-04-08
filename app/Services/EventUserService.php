<?php

namespace App\Services;

use App\Jobs\UpdateEventAttendeeCount;
use App\Models\Event;
use App\Models\EventLog;
use App\Models\EventUser;
use App\Models\User;
use App\Events\EventLogCreated;
use Illuminate\Validation\ValidationException;

class EventUserService
{
    public function join(User $user, Event $event): EventUser
    {
        $existing = EventUser::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        $alreadyJoined = $existing && $existing->status === EventUser::STATUS_JOINED;

        if ($event->limit !== null) {
            if (! $alreadyJoined) {
                $joinedCount = EventUser::query()
                    ->where('event_id', $event->id)
                    ->where('status', EventUser::STATUS_JOINED)
                    ->count();

                if ($joinedCount >= $event->limit) {
                    throw ValidationException::withMessages([
                        'limit' => 'Event has reached the participant limit.',
                    ]);
                }
            }
        }

        if ($alreadyJoined && $existing) {
            return $existing->fresh();
        }

        $eventUser = EventUser::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->id],
            ['status' => EventUser::STATUS_JOINED],
        );

        $log = EventLog::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'action' => 'join',
        ])->load('user:id,name,email');

        broadcast(new EventLogCreated($log));
        UpdateEventAttendeeCount::dispatch($event->id);

        return $eventUser->fresh();
    }

    public function leave(User $user, Event $event): void
    {
        $eventUser = EventUser::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        if ($eventUser) {
            $eventUser->delete();
            $log = EventLog::create([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'action' => 'leave',
            ])->load('user:id,name,email');

            broadcast(new EventLogCreated($log));
            UpdateEventAttendeeCount::dispatch($event->id);
        }
    }
}
