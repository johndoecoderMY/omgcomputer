<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppInboundMessage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class WhatsAppInboxController extends Controller
{
    public function index(): View
    {
        return view('reports.integrations.whatsapp-inbox', [
            'messages' => $this->latestMessages(),
        ]);
    }

    public function poll(): JsonResponse
    {
        return response()->json([
            'messages' => $this->latestMessages()->map(fn (WhatsAppInboundMessage $message): array => [
                'id' => $message->id,
                'wa_id' => $message->wa_id,
                'contact_name' => $message->contact_name,
                'message_type' => $message->message_type,
                'message_body' => $message->message_body,
                'received_at' => $message->received_at->toIso8601String(),
            ]),
            'checked_at' => now()->toIso8601String(),
        ])->header('Cache-Control', 'no-store, private');
    }

    /**
     * @return Collection<int, WhatsAppInboundMessage>
     */
    private function latestMessages(): Collection
    {
        return WhatsAppInboundMessage::query()
            ->latest('received_at')
            ->limit(100)
            ->get();
    }
}
