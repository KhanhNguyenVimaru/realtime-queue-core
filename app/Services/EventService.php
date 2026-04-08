<?php

namespace App\Services;

use App\Models\Event;
class EventService
{
    public function create(int $hostId, array $data): Event
    {
        $event = new Event();
        $event->forceFill([
            'host_id' => $hostId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'img' => $data['img'] ?? null,
            'limit' => $data['limit'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ]);
        $event->save();

        return $event;
    }

    public function update(Event $event, array $data): Event
    {
        $event->forceFill([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'img' => $data['img'] ?? null,
            'limit' => $data['limit'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ]);
        $event->save();

        return $event;
    }
}
