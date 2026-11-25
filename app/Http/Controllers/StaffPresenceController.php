<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\StaffPresenceTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffPresenceController extends Controller
{
    public function update(Request $request, StaffPresenceTracker $presence): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless(
            $user->hasAnyRole(['admin', 'member']) && ! $user->hasRole('customer'),
            403,
        );

        $validated = $request->validate([
            'client_id' => ['required', 'uuid'],
            'active' => ['required', 'boolean'],
            'realtime_ready' => ['required', 'boolean'],
            'include_roster' => ['sometimes', 'boolean'],
        ]);

        $presence->update(
            $user,
            $validated['client_id'],
            (bool) $validated['active'],
            (bool) $validated['realtime_ready'],
        );

        if (! (bool) ($validated['include_roster'] ?? false)) {
            return response()->json([]);
        }

        return response()->json([
            'onlineStaff' => $presence->onlineStaff()
                ->map(fn (User $staff): array => [
                    'name' => $staff->name,
                    'role' => $staff->hasRole('admin') ? 'Admin' : 'Staff',
                ])
                ->values(),
        ]);
    }
}
