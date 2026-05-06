<?php

namespace App\QueryBuilders;

use App\Models\EventLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class EventLogQueryBuilder
{
    public static function paginateIndex(Request $request, User $user): LengthAwarePaginator
    {
        $perPage = max(1, min($request->integer('per_page', 15), 100));

        return static::buildQuery($request, $user)->paginate($perPage);
    }

    public static function buildQuery(Request $request, User $user): Builder
    {
        $query = EventLog::query()
            ->with(['user:id,name,email', 'event:id,title'])
            ->latest();

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $like = '%' . $search . '%';

            $query->where(function (Builder $innerQuery) use ($like): void {
                $innerQuery
                    ->whereHas('user', function (Builder $userQuery) use ($like): void {
                        $userQuery->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    })
                    ->orWhereHas('event', function (Builder $eventQuery) use ($like): void {
                        $eventQuery->where('title', 'like', $like);
                    });
            });
        }

        $action = $request->input('action');
        if ($action !== null && $action !== '') {
            $query->where('action', $action);
        }

        $eventId = $request->integer('event_id');
        if ($eventId > 0) {
            $query->where('event_id', $eventId);
        }

        $userId = $request->integer('user_id');
        if ($userId > 0 && $user->isAdmin()) {
            $query->where('user_id', $userId);
        }

        return $query;
    }
}
