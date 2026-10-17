<p class="text-body-secondary">Deleting your account permanently removes your login and cannot be undone. Confirm your current password to continue.</p>
<form method="POST" action="{{ route('profile.destroy') }}">
    @csrf
    @method('DELETE')
    <label class="form-label" for="delete-account-password">Current password</label>
    <div class="d-flex flex-wrap gap-2">
        <input class="form-control @error('password', 'userDeletion') is-invalid @enderror"
            id="delete-account-password" name="password" type="password" autocomplete="current-password" required
            style="max-width: 24rem">
        <button class="btn btn-danger" type="submit"
            onclick="return confirm('Permanently delete your account? This cannot be undone.');">Delete my account</button>
    </div>
    @error('password', 'userDeletion')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
</form>
