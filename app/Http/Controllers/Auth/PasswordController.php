<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                Password::defaults(),
                'confirmed',
                function (string $attribute, mixed $value, \Closure $fail) use ($user): void {
                    $mailbox = $user->emailMailbox;
                    if ($mailbox && hash_equals($mailbox->password, (string) $value)) {
                        $fail('Your OMG Computer account password must be different from your cPanel mailbox password.');
                    }
                },
            ],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);
        $user->profileActivities()->create(['event' => 'password_updated']);

        return back()
            ->with('status', 'password-updated')
            ->with('profile-tab', 'security');
    }
}
