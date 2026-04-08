<?php

namespace App\QueryBuilders;

use App\Models\Event;
use App\Models\EventLog;
use App\Models\EventUser;
use Illuminate\Database\Eloquent\Builder;

class EventQueryBuilder
{
    public static function buildQuery(array $filters, ?int $userId = null): Builder
    {
        $query = static::apply(
            Event::query()->select(['id', 'host_id', 'title', 'description', 'img', 'limit', 'starts_at', 'ends_at', 'created_at', 'updated_at']),
            $filters
        );

        return static::applyEnrollmentMeta($query, $userId);
    }

    public static function apply(Builder $query, array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $innerQuery) use ($search): void {
                $like = '%' . $search . '%';

                $innerQuery
                    ->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        $sortBy = $filters['sort_by'] ?? 'latest';
        if ($sortBy === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $hostId = $filters['host_id'] ?? null;
        if ($hostId !== null && $hostId !== '') {
            $query->where('host_id', $hostId);
        }

        $endDate = $filters['end_date'] ?? null;
        if ($endDate !== null && $endDate !== '') {
            $query->whereDate('ends_at', $endDate);
        }

        return $query;
    }

    public static function applyEnrollmentMeta(Builder $query, ?int $userId): Builder
    {
        $query->withCount([
            'attendees as joined_count' => function (Builder $attendees): void {
                $attendees->where('event_user.status', EventUser::STATUS_JOINED);
            },
        ]);

        if ($userId) {
            $query->withExists([
                'attendees as joined' => function (Builder $attendees) use ($userId): void {
                    $attendees
                        ->where('event_user.status', EventUser::STATUS_JOINED)
                        ->where('users.id', $userId);
                },
            ]);
        } else {
            $query->selectRaw('false as joined');
        }

        return $query;
    }

    public static function buildDashboardPayload(Event $event, ?int $userId, int $perPage): array
    {
        $detail = static::applyEnrollmentMeta(
            Event::query()->select(['id', 'host_id', 'title', 'description', 'img', 'limit', 'starts_at', 'ends_at', 'created_at', 'updated_at']),
            $userId
        )
            ->whereKey($event->id)
            ->firstOrFail();

        $logs = EventLog::query()
            ->where('event_id', $event->id)
            ->with('user:id,name,email')
            ->latest()
            ->paginate($perPage);

        return [
            'event' => $detail,
            'logs' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ];
    }
}
