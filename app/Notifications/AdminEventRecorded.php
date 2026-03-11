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
        return [
            'event_id' => $this->event->id,
            'event_type' => $this->event->event_type,
            'action' => $this->event->action,
            'title' => $this->event->title,
            'message' => $this->event->message,
            'url' => $this->event->url,
            'actor_id' => $this->event->actor_id,
            'created_at' => optional($this->event->created_at)?->toDateTimeString(),
        ];
    }
}
