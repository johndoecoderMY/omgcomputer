<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\WorkspaceNotification;
use Illuminate\Database\Eloquent\Collection;

class WorkspaceNotifier
{
    public function notifyUsers(
        iterable $users,
        ?User $actor,
        string $title,
        string $message,
        string $url,
    ): void {
        $recipients = collect($users)
            ->filter(fn ($user): bool => $user instanceof User && (! $actor || $user->isNot($actor)))
            ->unique('id');

        foreach ($recipients as $recipient) {
            $recipient->notify(new WorkspaceNotification($title, $message, $url));
        }
    }

    public function notifyAdmins(?User $actor, string $title, string $message, string $url): void
    {
        /** @var Collection<int, User> $admins */
        $admins = User::query()
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'admin'))
            ->get();

        $this->notifyUsers($admins, $actor, $title, $message, $url);
    }
}
