<?php

namespace App\Http\Controllers;

use App\Models\MetaAdsConnection;
use App\Models\MetaDataDeletionRequest;
use App\Models\MetaPageConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use JsonException;
use Symfony\Component\HttpFoundation\Response;

class MetaDataDeletionController extends Controller
{
    public function callback(Request $request): JsonResponse
    {
        $appSecret = config('services.meta.app_secret');
        $signedRequest = $request->input('signed_request');
        if (! is_string($appSecret) || $appSecret === '' || ! is_string($signedRequest) || strlen($signedRequest) > 8192) {
            return response()->json(['error' => 'Invalid signed request.'], Response::HTTP_BAD_REQUEST);
        }

        $parts = explode('.', $signedRequest);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return response()->json(['error' => 'Invalid signed request.'], Response::HTTP_BAD_REQUEST);
        }

        $signature = $this->decodeBase64Url($parts[0]);
        $payloadBytes = $this->decodeBase64Url($parts[1]);
        if ($signature === null || $payloadBytes === null) {
            return response()->json(['error' => 'Invalid signed request.'], Response::HTTP_BAD_REQUEST);
        }

        $expectedSignature = hash_hmac('sha256', $parts[1], $appSecret, true);
        if (! hash_equals($expectedSignature, $signature)) {
            return response()->json(['error' => 'Invalid signed request signature.'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $payload = json_decode($payloadBytes, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response()->json(['error' => 'Invalid signed request payload.'], Response::HTTP_BAD_REQUEST);
        }

        if (! is_array($payload)
            || strtoupper((string) ($payload['algorithm'] ?? '')) !== 'HMAC-SHA256'
            || (! is_string($payload['user_id'] ?? null) && ! is_int($payload['user_id'] ?? null))) {
            return response()->json(['error' => 'Invalid signed request payload.'], Response::HTTP_BAD_REQUEST);
        }

        $metaUserId = (string) $payload['user_id'];
        if (! ctype_digit($metaUserId)) {
            return response()->json(['error' => 'Invalid signed request user.'], Response::HTTP_BAD_REQUEST);
        }

        $confirmationCode = Str::random(48);
        DB::transaction(function () use ($metaUserId, $confirmationCode): void {
            MetaAdsConnection::query()->where('meta_user_id', $metaUserId)->delete();
            MetaPageConnection::query()->where('meta_user_id', $metaUserId)->delete();

            MetaDataDeletionRequest::query()->create([
                'confirmation_code_hash' => hash('sha256', $confirmationCode),
                'completed_at' => now(),
            ]);
        });

        return response()->json([
            'url' => route('data-deletion.status', ['confirmationCode' => $confirmationCode]),
            'confirmation_code' => $confirmationCode,
        ]);
    }

    public function status(string $confirmationCode): View
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{48}$/', $confirmationCode) === 1, 404);
        $deletionRequest = MetaDataDeletionRequest::query()
            ->where('confirmation_code_hash', hash('sha256', $confirmationCode))
            ->firstOrFail();

        return view('pages.data-deletion-status', ['deletionRequest' => $deletionRequest]);
    }

    private function decodeBase64Url(string $encoded): ?string
    {
        if (preg_match('/^[A-Za-z0-9_-]+$/', $encoded) !== 1) {
            return null;
        }

        $decoded = base64_decode(strtr($encoded, '-_', '+/').str_repeat('=', (4 - strlen($encoded) % 4) % 4), true);

        return $decoded === false ? null : $decoded;
    }
}
