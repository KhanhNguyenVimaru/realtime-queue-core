<?php

namespace App\Http\Controllers;

use App\Models\EventUser;
use App\Models\Event;
use App\Services\EventUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventUserController extends Controller
{
    public function __construct(private EventUserService $eventUserService)
    {
    }

    public function join(Request $request, Event $event): JsonResponse
    {
        $eventUser = $this->eventUserService->join($request->user(), $event);

        return response()->json([
            'message' => 'Joined event successfully.',
            'event_user' => $eventUser,
        ], 202);
    }

    public function leave(Request $request, Event $event): JsonResponse
    {
        $this->eventUserService->leave($request->user(), $event);

        return response()->json([
            'message' => 'Left event successfully.',
        ]);
    }
}
