<form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PATCH')

    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="photo">Profile photo</label>
            <x-image-upload-input id="photo" name="photo" :multiple="false" :required="false" input-class="@error('photo') is-invalid @enderror" />
            <div class="form-text">Upload a JPG, PNG, or WebP image up to 10 MB.</div>
            @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @if ($user->profile_photo_path)
                <div class="form-check mt-2">
                    <input class="form-check-input" id="remove_photo" name="remove_photo" type="checkbox" value="1">
                    <label class="form-check-label" for="remove_photo">Remove current photo</label>
                </div>
            @endif
        </div>
        <div class="col-md-6">
            <label class="form-label" for="name">Full name</label>
            <input class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                value="{{ old('name', $user->name) }}" maxlength="255" autocomplete="name" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="email">Email address</label>
            <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email"
                value="{{ old('email', $user->email) }}" maxlength="255" autocomplete="email" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="phone">Phone number</label>
            <input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone"
                value="{{ old('phone', $user->phone) }}" maxlength="50" autocomplete="tel">
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label" for="address">Address</label>
            <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address"
                rows="3" maxlength="2000" autocomplete="street-address">{{ old('address', $user->address) }}</textarea>
            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="d-flex justify-content-end mt-3"><button class="btn btn-primary" type="submit">Save profile</button></div>
</form>
