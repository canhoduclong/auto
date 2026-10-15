<?php

namespace App\Notifications;

use App\Models\AdminEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminEventRecorded extends Notification
{
    use Queueable;

    public function __construct(private readonly AdminEvent $event)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $payload = [
            'event_id' => $this->event->id,
            'event_type' => $this->event->event_type,
            'action' => $this->event->action,
            'title' => $this->event->title,
            'message' => $this->event->message,
            'url' => $this->event->url,
            'actor_id' => $this->event->actor_id,
            'metadata' => $this->event->metadata ?? [],
            'created_at' => optional($this->event->created_at)?->toDateTimeString(),
        ];

        // notifications.data is TEXT (65,535 bytes). Large audit snapshots
        // belong in admin_events.metadata, not in a copy per recipient.
        if (strlen(json_encode($payload, JSON_THROW_ON_ERROR)) > 60000) {
            $payload['metadata'] = [
                'admin_event_id' => $this->event->id,
                'details_stored_in_admin_event' => true,
            ];
            $payload['title'] = mb_substr((string) $payload['title'], 0, 255);
            $payload['message'] = mb_substr((string) $payload['message'], 0, 1000);
            $payload['url'] = $payload['url'] === null ? null : mb_substr((string) $payload['url'], 0, 2048);
        }

        return $payload;
    }
}
