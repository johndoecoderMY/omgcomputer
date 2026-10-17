<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class FirstLoginPasswordController extends Controller
{
    public function create(Request $request): RedirectResponse|View
    {
        if (! $request->user()->force_password_change) {
            return redirect()->route('dashboard');
        }

        return view('auth.first-login-password');
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->force_password_change, 404);

        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'different:current_password'],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($data['password']),
            'force_password_change' => false,
        ])->save();
        $request->user()->profileActivities()->create(['event' => 'initial_password_changed']);

        return redirect()->intended(route('dashboard'))->with('status', 'initial-password-updated');
    }
}
