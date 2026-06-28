<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DepartmentBroadcastNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $title,
        private readonly string $message,
        private readonly array $targetRoles,
        private readonly array $targetRoleNames = [],
        private readonly ?string $expiresAt = null,
        private readonly ?string $broadcastId = null,
        private readonly ?string $url = null,
        private readonly ?int $senderId = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'department_broadcast',
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'target_roles' => $this->targetRoles,
            'target_role_names' => $this->targetRoleNames,
            'expires_at' => $this->expiresAt,
            'broadcast_id' => $this->broadcastId,
            'sender_id' => $this->senderId,
        ];
    }
}
