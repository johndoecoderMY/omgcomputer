<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Support\DataTableQueryBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Spatie\QueryBuilder\AllowedFilter;
use Yajra\DataTables\Facades\DataTables;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user()->load('teams.department'),
        ]);
    }

    public function showMember(User $member): View|RedirectResponse
    {
        if (auth()->id() === $member->id) {
            return redirect()->route('profile.edit');
        }

        $isCustomerAccount = $member->hasRole('customer') && ! $member->hasAnyRole(['admin', 'member']);

        return view('profile.member', [
            'user' => $member->load(['teams.department', 'emailMailbox']),
            'canManageAccount' => auth()->user()->hasRole('admin'),
            'isCustomerAccount' => $isCustomerAccount,
            'cpanelProvisioningEnabled' => (bool) config('services.cpanel.enabled'),
        ]);
    }

    public function activity(Request $request)
    {
        return DataTables::eloquent(DataTableQueryBuilder::for(
            $request->user()->profileActivities()->getQuery(),
            $request,
            [
                'event' => AllowedFilter::partial('event'),
                'created_at' => AllowedFilter::partial('created_at'),
            ],
        ))
            ->editColumn('created_at', fn ($activity): string => $activity->created_at->format('M j, Y g:i A'))
            ->editColumn('event', fn ($activity): string => match ($activity->event) {
                'profile_updated' => 'Profile details updated',
                'password_updated' => 'Password changed',
                'account_deleted' => 'Account deleted',
                default => str($activity->event)->replace('_', ' ')->title()->toString(),
            })
            ->editColumn('changed_fields', fn ($activity): string => collect($activity->changed_fields ?? [])
                ->map(fn (string $field): string => str($field)->replace('_', ' ')->title()->toString())
                ->join(', '))
            ->toJson();
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->except(['photo', 'remove_photo']);
        $previousPhotoPath = $user->profile_photo_path;
        $user->fill($data);

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('profile-photos', 'public');
            if ($photoPath === false) {
                throw new \RuntimeException('The profile photo could not be stored.');
            }
            $user->profile_photo_path = $photoPath;
        } elseif ($request->boolean('remove_photo')) {
            $user->profile_photo_path = null;
        }
        $changedFields = array_keys($user->getDirty());

        $user->save();
        if ($previousPhotoPath !== null && $previousPhotoPath !== $user->profile_photo_path) {
            if (! Storage::disk('public')->delete($previousPhotoPath)) {
                throw new \RuntimeException('The previous profile photo could not be removed.');
            }
        }

        if ($changedFields !== []) {
            $user->profileActivities()->create([
                'event' => 'profile_updated',
                'changed_fields' => $changedFields,
            ]);
        }

        return Redirect::route('profile.edit')
            ->with('status', 'profile-updated')
            ->with('profile-tab', 'overview');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $user->profileActivities()->create(['event' => 'account_deleted']);
        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
