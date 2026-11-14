<?php

namespace App\Notifications;

use App\Models\User;
use App\Services\BeamsPushNotifier;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public string $url,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        if ($notifiable instanceof User
            && $notifiable->receivesPushNotifications()
            && app(BeamsPushNotifier::class)->isConfigured()) {
            $channels[] = BeamsPushNotifier::class;
        }

        if ($notifiable instanceof User
            && $notifiable->canReceiveStaffNotificationEmail()
            && $notifiable->receivesEmailNotifications()) {
            $channels[] = 'mail';
        }

        return $channels;
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
        return (new BroadcastMessage($this->toArray($notifiable)))->onConnection('sync');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->view('emails.workspace-notification', [
                'recipientName' => $notifiable->name,
                'notificationTitle' => $this->title,
                'notificationMessage' => $this->message,
                'actionUrl' => $this->url,
            ]);
    }

}
