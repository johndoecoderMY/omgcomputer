<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class IntegrationTestNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public string $url,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
