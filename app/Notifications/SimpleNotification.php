<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Generic notification used across the app — title + description + optional
 * link/icon/color. Persisted to the database channel; the Vue navbar reads it
 * via the /v2/notifications endpoint.
 */
class SimpleNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public ?string $description = null,
        public ?string $link = null,
        public string $color = 'info',
        public string $icon = 'i-heroicons-bell',
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'link' => $this->link,
            'color' => $this->color,
            'icon' => $this->icon,
        ];
    }
}
