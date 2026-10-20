<form method="POST" action="{{ route('password.update') }}">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label" for="update_password_current_password">Current password</label>
        <input class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
            id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" required>
        @error('current_password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="update_password_password">New password</label>
            <input class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                id="update_password_password" name="password" type="password" autocomplete="new-password" required>
            @error('password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="d-flex justify-content-end mt-3">
        <button class="btn btn-primary" type="submit">Change password</button>
    </div>
</form>
