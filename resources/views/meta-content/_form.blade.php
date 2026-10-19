<form class="card p-2" method="POST" action="{{ $action }}" enctype="multipart/form-data">
    @csrf
    @if ($method === 'PUT')
        @method('PUT')
    @endif
    <div class="card-body row g-3">
        @if ($errors->any())
            <div class="col-12 alert alert-danger mb-0" role="alert">
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif
        <div class="col-md-6">
            <label class="form-label" for="meta-platform">Publish to</label>
            <select class="form-select" id="meta-platform" name="platform" required>
                <option value="facebook" @selected(old('platform', $post?->platform ?? 'facebook') === 'facebook')>Facebook Page</option>
                <option value="instagram" @selected(old('platform', $post?->platform) === 'instagram')>Instagram professional account</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="meta-media-type">Post format</label>
            <select class="form-select" id="meta-media-type" name="media_type" required>
                <option value="text" @selected(old('media_type', $post?->media_type ?? 'text') === 'text')>Text post (Facebook only)</option>
                <option value="image" @selected(old('media_type', $post?->media_type) === 'image')>Image post</option>
                <option value="reel" @selected(old('media_type', $post?->media_type) === 'reel')>Reel (MP4)</option>
            </select>
            @error('media_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label" for="meta-caption">Caption or post text</label>
            <textarea class="form-control" id="meta-caption" name="caption" rows="5" maxlength="63206">{{ old('caption', $post?->caption) }}</textarea>
            <div class="form-text">Facebook Page text supports up to 63,206 characters; Instagram captions are limited to 2,200.</div>
            @error('caption')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label" for="meta-media">Image or video</label>
            <input class="form-control" id="meta-media" name="media" type="file" accept="image/jpeg,image/png,image/webp,video/mp4">
            <div class="form-text">Images: JPEG, PNG, or WebP (Instagram requires JPEG). Reels: MP4. Maximum upload size: 100 MB.</div>
            @if ($post?->media_path)
                <div class="small text-body-secondary mt-2">A media file is already attached. Upload a replacement only if needed.</div>
            @endif
            @error('media')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="meta-scheduled-at">Schedule for (optional)</label>
            <input class="form-control" id="meta-scheduled-at" name="scheduled_at" type="datetime-local"
                value="{{ old('scheduled_at', $post?->scheduled_at?->format('Y-m-d\TH:i')) }}">
            @error('scheduled_at')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <div class="alert alert-warning mb-0">
                Meta must be able to access the media URL over the public internet. Uploading files to a local-only
                development URL will not work with Meta publishing.
            </div>
        </div>
    </div>
    <div class="card-footer bg-body d-flex justify-content-between">
        <a class="btn btn-outline-secondary" href="{{ route('meta-content.index') }}">Cancel</a>
        <div class="d-flex gap-2">
            @if (! $post)
                <button class="btn btn-primary" type="submit" name="publish_now" value="1">Publish now</button>
            @endif
            <button class="btn btn-outline-primary" type="submit">{{ $submitLabel }}</button>
        </div>
    </div>
</form>
<script>
    (() => {
        const platform = document.getElementById('meta-platform');
        const format = document.getElementById('meta-media-type');
        const updateFormats = () => {
            const textOption = format.querySelector('option[value="text"]');
            textOption.disabled = platform.value === 'instagram';
            if (platform.value === 'instagram' && format.value === 'text') {
                format.value = 'image';
            }
        };
        platform.addEventListener('change', updateFormats);
        updateFormats();
    })();
</script>
