<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AssignmentAccess
{
    public static function users(): Builder
    {
        return User::query()
            ->whereHas('roles', fn ($roles) => $roles->whereIn('name', ['admin', 'member']))
            ->whereDoesntHave('roles', fn ($roles) => $roles->where('name', 'customer'))
            ->orderBy('name');
    }

    public static function preventNonAdminAssignment(Request $request, array $fields): void
    {
        if ($request->user()->hasRole('admin')) {
            return;
        }

        foreach ($fields as $field) {
            if ($request->exists($field)) {
                throw ValidationException::withMessages([
                    $field => 'Only admins can assign people.',
                ]);
            }
        }
    }
}
