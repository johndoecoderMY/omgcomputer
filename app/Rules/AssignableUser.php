<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AssignableUser implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $isAssignable = User::query()
            ->whereKey($value)
            ->whereHas('roles', fn ($roles) => $roles->whereIn('name', ['admin', 'member']))
            ->whereDoesntHave('roles', fn ($roles) => $roles->where('name', 'customer'))
            ->exists();

        if (! $isAssignable) {
            $fail('Choose an admin or member account to assign.');
        }
    }
}
