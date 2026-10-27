<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\CpanelEmailProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request, CpanelEmailProvisioner $provisioner): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults()],
        ]);

        $user = $request->user();
        try {
            $provisioner->syncUserPassword($user, $validated['password']);
        } catch (RuntimeException $exception) {
            Log::warning('Mailbox password synchronization failed during a password update.', [
                'user_id' => $user->id,
                'exception' => $exception::class,
            ]);

            return back()->withErrors([
                'password' => 'Your password was not changed because the connected mailbox password could not be updated. Please try again or contact an administrator.',
            ], 'updatePassword')->with('profile-tab', 'security');
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);
        $user->profileActivities()->create(['event' => 'password_updated']);

        return back()
            ->with('status', 'password-updated')
            ->with('profile-tab', 'security');
    }
}
