<?php

namespace App\Http\Controllers;

use App\QueryBuilders\EventLogQueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $logs = EventLogQueryBuilder::paginateIndex($request, $user);

        return response()->json(paginate_payload($logs, 'logs'));
    }
}
