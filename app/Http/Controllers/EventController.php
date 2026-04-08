<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\QueryBuilders\EventQueryBuilder;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(private EventService $eventService){
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min($request->integer('per_page', 10), 100));

        $events = EventQueryBuilder::buildQuery(
            $request->only(['search', 'sort_by', 'host_id']),
            $request->user()?->id
        )->paginate($perPage);

        return response()->json(paginate_payload($events, 'events'));
    }

    public function show(Event $event): JsonResponse
    {
        $detail = EventQueryBuilder::applyEnrollmentMeta(
            Event::query()->select(['id', 'host_id', 'title', 'description', 'img', 'limit', 'starts_at', 'ends_at', 'created_at', 'updated_at']),
            request()->user()?->id
        )->whereKey($event->id)->firstOrFail();

        return response()->json([
            'event' => $detail,
        ]);
    }

    public function dashboard(Request $request, Event $event): JsonResponse
    {
        if ($event->host_id !== $request->user()?->id) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $perPage = max(1, min($request->integer('per_page', 10), 50));
        return response()->json(
            EventQueryBuilder::buildDashboardPayload($event, $request->user()?->id, $perPage)
        );
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = $this->eventService->create($request->user()->id, $request->validated());

        return response()->json([
            'message' => 'Event created successfully.',
            'event' => $event->fresh(),
        ], 201);
    }

    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        $event = $this->eventService->update($event, $request->validated());

        return response()->json([
            'message' => 'Event updated successfully.',
            'event' => $event->fresh(),
        ]);
    }

    public function destroy(Event $event): JsonResponse
    {
        $event->delete();

        return response()->json([
            'message' => 'Event deleted successfully.',
        ]);
    }

}
