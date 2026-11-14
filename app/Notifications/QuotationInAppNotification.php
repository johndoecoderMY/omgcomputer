<?php

namespace App\Notifications;

use App\Models\BillingDocument;
use App\Models\User;
use App\Services\BeamsPushNotifier;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class QuotationInAppNotification extends Notification
{
    public function __construct(public BillingDocument $document) {}

    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        if ($notifiable instanceof User && app(BeamsPushNotifier::class)->isConfigured()) {
            $channels[] = BeamsPushNotifier::class;
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Quotation '.$this->document->number,
            'message' => 'Your quotation is ready to review.',
            'url' => route('billing.documents.show', $this->document),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage($this->toArray($notifiable)))->onConnection('sync');
    }
}
