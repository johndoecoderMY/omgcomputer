<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppInboundMessage;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use JsonException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $verifyToken = config('services.meta.webhook_verify_token');
        $mode = $request->query('hub.mode', $request->query('hub_mode'));
        $token = $request->query('hub.verify_token', $request->query('hub_verify_token'));
        $challenge = $request->query('hub.challenge', $request->query('hub_challenge'));

        abort_unless(
            $mode === 'subscribe'
            && is_string($verifyToken)
            && $verifyToken !== ''
            && is_string($token)
            && hash_equals($verifyToken, $token)
            && is_string($challenge),
            HttpResponse::HTTP_FORBIDDEN,
        );

        return response($challenge, HttpResponse::HTTP_OK)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function receive(Request $request): Response
    {
        $appSecret = config('services.meta.app_secret');
        $signature = $request->header('X-Hub-Signature-256');
        if (! is_string($appSecret) || $appSecret === '' || ! is_string($signature)) {
            abort(HttpResponse::HTTP_UNAUTHORIZED);
        }

        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);
        abort_unless(hash_equals($expectedSignature, $signature), HttpResponse::HTTP_UNAUTHORIZED);

        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            abort(HttpResponse::HTTP_BAD_REQUEST);
        }

        if (! is_array($payload) || ($payload['object'] ?? null) !== 'whatsapp_business_account') {
            return response('', HttpResponse::HTTP_OK);
        }

        $configuredPhoneNumberId = (string) config('services.meta.whatsapp_phone_number_id', '');
        foreach (is_array($payload['entry'] ?? null) ? $payload['entry'] : [] as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            foreach (is_array($entry['changes'] ?? null) ? $entry['changes'] : [] as $change) {
                if (! is_array($change)) {
                    continue;
                }
                $value = $change['value'] ?? null;
                if (! is_array($value) || ($change['field'] ?? null) !== 'messages') {
                    continue;
                }

                $phoneNumberId = (string) ($value['metadata']['phone_number_id'] ?? '');
                if ($configuredPhoneNumberId === '' || ! hash_equals($configuredPhoneNumberId, $phoneNumberId)) {
                    continue;
                }

                $contacts = collect(is_array($value['contacts'] ?? null) ? $value['contacts'] : [])
                    ->filter(fn (mixed $contact): bool => is_array($contact) && isset($contact['wa_id']))
                    ->keyBy(fn (array $contact): string => (string) $contact['wa_id']);

                foreach (is_array($value['messages'] ?? null) ? $value['messages'] : [] as $message) {
                    if (! is_array($message)
                        || ! is_string($message['id'] ?? null)
                        || ! is_string($message['from'] ?? null)
                        || ! is_string($message['type'] ?? null)
                        || ! is_numeric($message['timestamp'] ?? null)) {
                        continue;
                    }

                    $type = mb_substr($message['type'], 0, 32);
                    $body = $this->messageBody($message, $type);
                    $contact = $contacts->get($message['from']);
                    $timestamp = (int) $message['timestamp'];

                    WhatsAppInboundMessage::query()->firstOrCreate(
                        ['meta_message_id' => $message['id']],
                        [
                            'phone_number_id' => $phoneNumberId,
                            'wa_id' => mb_substr($message['from'], 0, 32),
                            'contact_name' => is_array($contact) && is_string($contact['profile']['name'] ?? null)
                                ? mb_substr($contact['profile']['name'], 0, 255)
                                : null,
                            'message_type' => $type,
                            'message_body' => $body === null ? null : mb_substr($body, 0, 10000),
                            'received_at' => CarbonImmutable::createFromTimestamp($timestamp),
                        ],
                    );
                }
            }
        }

        return response('', HttpResponse::HTTP_OK);
    }

    private function messageBody(array $message, string $type): ?string
    {
        $text = is_array($message['text'] ?? null) ? $message['text'] : [];
        $button = is_array($message['button'] ?? null) ? $message['button'] : [];
        $interactive = is_array($message['interactive'] ?? null) ? $message['interactive'] : [];
        $buttonReply = is_array($interactive['button_reply'] ?? null) ? $interactive['button_reply'] : [];
        $listReply = is_array($interactive['list_reply'] ?? null) ? $interactive['list_reply'] : [];
        $body = match ($type) {
            'text' => $text['body'] ?? null,
            'button' => $button['text'] ?? null,
            'interactive' => $buttonReply['title'] ?? $listReply['title'] ?? null,
            default => '['.ucfirst($type).' message]',
        };

        return is_string($body) ? $body : null;
    }
}
