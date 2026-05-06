<?php

use App\Models\Event;
use App\Models\EventLog;
use App\Models\User;
use App\Events\EventLogCreated;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

if (! function_exists('create_event_log')) {
    function create_event_log(User $user, Event $event, string $action): EventLog
    {
        $log = EventLog::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'action' => $action,
        ])->load(['user:id,name,email', 'event:id,title']);

        broadcast(new EventLogCreated($log));

        return $log;
    }
}

if (! function_exists('user_payload')) {
    function user_payload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'created_at' => optional($user->created_at)?->toIso8601String(),
            'updated_at' => optional($user->updated_at)?->toIso8601String(),
        ];
    }
}

if (! function_exists('pagination_meta')) {
    function pagination_meta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}

if (! function_exists('paginate_payload')) {
    function paginate_payload(LengthAwarePaginator $paginator, string $key = 'data'): array
    {
        return [
            $key => $paginator->items(),
            'meta' => pagination_meta($paginator),
        ];
    }
}
