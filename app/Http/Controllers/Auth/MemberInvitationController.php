<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class MemberInvitationController extends Controller
{
    public function show(User $user, string $token): View|RedirectResponse
    {
        abort_unless(Password::tokenExists($user, $token), 403, 'This membership invitation is invalid or has expired.');

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')->with('status', 'This email address is already verified. You can sign in or reset your password.');
        }

        return view('auth.member-invitation', [
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function complete(Request $request, User $user, string $token): RedirectResponse
    {
        abort_unless(Password::tokenExists($user, $token), 403, 'This membership invitation is invalid or has expired.');

        $data = $request->validate([
            'password' => ['required', Rules\Password::defaults(), 'confirmed'],
        ]);

        $status = Password::reset([
            'email' => $user->email,
            'token' => $token,
            'password' => $data['password'],
        ], function (User $user) use ($data): void {
            $user->forceFill([
                'password' => Hash::make($data['password']),
                'email_verified_at' => now(),
                'force_password_change' => false,
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['password' => __($status)]);
        }

        return redirect()->route('login')
            ->with('status', 'Your email is verified and password is set. You can now sign in.');
    }
}
