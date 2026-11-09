<form class="card p-2" method="POST" action="{{ $action }}" enctype="multipart/form-data">
    @csrf
    @if ($method === 'PUT')
        @method('PUT')
    @endif
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <p class="fw-semibold mb-1">Please correct the following:</p>
                <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label" for="title">Project / website name</label>
                <input class="form-control" id="title" name="title" maxlength="180" value="{{ old('title', $preview->title) }}" required>
                @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="category">Category <span class="text-body-secondary">(optional)</span></label>
                <input class="form-control" id="category" name="category" maxlength="120" value="{{ old('category', $preview->category) }}" placeholder="Business, portfolio, online store…">
                @error('category')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="description">Description <span class="text-body-secondary">(optional)</span></label>
                <textarea class="form-control" id="description" name="description" rows="4" maxlength="5000">{{ old('description', $preview->description) }}</textarea>
                @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-8">
                <label class="form-label" for="preview_url">Live preview URL <span class="text-body-secondary">(optional)</span></label>
                <input class="form-control" type="url" id="preview_url" name="preview_url" maxlength="2048" value="{{ old('preview_url', $preview->preview_url) }}" placeholder="https://example.com">
                <div class="form-text">Opens in a new tab from the public portfolio.</div>
                @error('preview_url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="sort_order">Display order</label>
                <input class="form-control" type="number" id="sort_order" name="sort_order" min="0" max="100000" value="{{ old('sort_order', $preview->sort_order ?? 0) }}" required>
                @error('sort_order')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="image">Preview screenshot</label>
                <input class="form-control" type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" {{ $preview->exists ? '' : 'required' }}>
                <div class="form-text">JPEG, PNG, or WebP, up to 10 MB. Use an image you have permission to publish.</div>
                @if ($preview->exists && Storage::disk('public')->exists($preview->image_path))
                    <img class="img-thumbnail mt-3" src="{{ Storage::disk('public')->url($preview->image_path) }}" alt="Current preview screenshot" style="max-width: 360px">
                @endif
                @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input type="hidden" name="is_published" value="0">
                    <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" @checked(old('is_published', $preview->is_published))>
                    <label class="form-check-label" for="is_published">Publish on the public Website Previews page</label>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer bg-body d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('development.website-previews.manage') }}">Cancel</a>
        <button class="btn btn-primary" type="submit">{{ $buttonLabel }}</button>
    </div>
</form>
