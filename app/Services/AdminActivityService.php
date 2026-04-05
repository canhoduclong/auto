<?php

namespace App\Services;

use App\Models\AdminEvent;
use App\Models\User;
use App\Notifications\AdminEventRecorded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

class AdminActivityService
{
    public static function record(
        string $eventType,
        string $action,
        ?Model $subject,
        string $title,
        ?string $message = null,
        array $metadata = [],
        ?string $url = null
    ): AdminEvent {
        if (!Schema::hasTable('admin_events')) {
            return new AdminEvent();
        }

        $event = AdminEvent::create([
            'actor_id' => Auth::id(),
            'event_type' => $eventType,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'title' => $title,
            'message' => $message,
            'metadata' => $metadata,
            'url' => $url,
        ]);

        if (Schema::hasTable('notifications')) {
            $admins = User::query()
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'admin');
                })
                ->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new AdminEventRecorded($event));
            }
        }

        return $event;
    }
}
