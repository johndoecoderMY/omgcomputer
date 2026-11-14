<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Pusher\PushNotifications\PushNotifications;
use Throwable;

class BeamsPushNotifier
{
    private ?PushNotifications $client = null;

    public function isConfigured(): bool
    {
        return (bool) config('services.beams.enabled');
    }

    public function generateToken(User $user): array
    {
        return $this->client()->generateToken((string) $user->getAuthIdentifier());
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof User) {
            throw new \InvalidArgumentException('Pusher Beams notifications require a user recipient.');
        }

        if (! $this->isConfigured()) {
            return;
        }

        $data = $notification->toArray($notifiable);

        try {
            $this->client()->publishToUsers(
                [(string) $notifiable->getAuthIdentifier()],
                [
                    'web' => [
                        'notification' => [
                            'title' => (string) ($data['title'] ?? config('app.name')),
                            'body' => (string) ($data['message'] ?? ''),
                            'deep_link' => url((string) ($data['url'] ?? route('notifications.index'))),
                        ],
                    ],
                ],
            );
        } catch (Throwable $exception) {
            Log::error('Pusher Beams notification publish failed.', [
                'user_id' => $notifiable->getAuthIdentifier(),
                'notification_type' => $notification::class,
                'exception' => $exception,
            ]);
        }
    }

    private function client(): PushNotifications
    {
        if (! $this->isConfigured()) {
            throw new \LogicException('Pusher Beams is not configured.');
        }

        return $this->client ??= new PushNotifications([
            'instanceId' => (string) config('services.beams.instance_id'),
            'secretKey' => (string) config('services.beams.secret_key'),
        ]);
    }
}