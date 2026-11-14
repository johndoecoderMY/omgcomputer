<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BeamsPushNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BeamsAuthController extends Controller
{
    public function __invoke(Request $request, BeamsPushNotifier $beams): JsonResponse
    {
        abort_unless($beams->isConfigured(), 503, 'Push notifications are not configured.');

        $userId = (string) $request->user()->getAuthIdentifier();
        $requestedUserId = $request->query('user_id');

        abort_unless(
            is_string($requestedUserId) && hash_equals($userId, $requestedUserId),
            401,
            'The requested push notification user does not match your account.',
        );

        /** @var User $user */
        $user = $request->user();

        return response()
            ->json($beams->generateToken($user))
            ->header('Cache-Control', 'private, no-store');
    }
}