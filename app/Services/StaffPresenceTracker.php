<?php

namespace App\Services;

use App\Models\StaffPresenceSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class StaffPresenceTracker
{
    private const ONLINE_WINDOW_SECONDS = 300;

    public function update(User $user, string $clientId, bool $isActive, bool $realtimeReady): void
    {
        $user->presenceSessions()
            ->where('last_seen_at', '<', now()->subSeconds(self::ONLINE_WINDOW_SECONDS))
            ->delete();

        StaffPresenceSession::query()->updateOrCreate(
            [
                'user_id' => $user->getKey(),
                'client_id' => $clientId,
            ],
            [
                'is_active' => $isActive,
                'realtime_ready' => $isActive && $realtimeReady,
                'last_seen_at' => now(),
            ],
        );
    }

    public function isActivelyViewing(User $user): bool
    {
        return $user->presenceSessions()
            ->where('is_active', true)
            ->where('realtime_ready', true)
            ->where('last_seen_at', '>=', now()->subSeconds(self::ONLINE_WINDOW_SECONDS))
            ->exists();
    }

    /**
     * @return Collection<int, User>
     */
    public function onlineStaff(): Collection
    {
        return User::query()
            ->whereHas('roles', fn ($roles) => $roles->whereIn('name', ['admin', 'member']))
            ->whereDoesntHave('roles', fn ($roles) => $roles->where('name', 'customer'))
            ->whereHas('presenceSessions', fn ($sessions) => $sessions
                ->where('is_active', true)
                ->where('last_seen_at', '>=', now()->subSeconds(self::ONLINE_WINDOW_SECONDS)))
            ->with(['roles:id,name'])
            ->orderBy('name')
            ->get();
    }
}
