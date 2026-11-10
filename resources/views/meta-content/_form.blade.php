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
            <label class="form-label" for="meta-platform">{{ $post ? 'Platform' : 'Platforms' }}</label>
            @if ($post)
                <select class="form-select" id="meta-platform" name="platform" required>
                    <option value="facebook" @selected(old('platform', $post->platform) === 'facebook')>Facebook Page</option>
                    <option value="instagram" @selected(old('platform', $post->platform) === 'instagram')>Instagram professional account</option>
                    <option value="tiktok" @selected(old('platform', $post->platform) === 'tiktok')>TikTok (planning only)</option>
                </select>
            @else
                @php($selectedPlatforms = old('platforms', ['facebook']))
                @php($selectedPlatforms = is_array($selectedPlatforms) ? $selectedPlatforms : [])
                <select class="form-select js-platform-search" id="meta-platform" name="platforms[]" multiple required
                    data-placeholder="Search and select one or more platforms">
                    <option value="facebook" @selected(in_array('facebook', $selectedPlatforms, true))>Facebook Page</option>
                    <option value="instagram" @selected(in_array('instagram', $selectedPlatforms, true))>Instagram professional account</option>
                    <option value="tiktok" @selected(in_array('tiktok', $selectedPlatforms, true))>TikTok (planning only)</option>
                </select>
                <div class="form-text">The same caption and media will be saved as a separate post for each selected platform.</div>
            @endif
            @error('platforms')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            @error('platforms.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
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
            <x-image-upload-input id="meta-media" name="media" :multiple="false" :required="false" accept="image/jpeg,image/png,image/webp,video/mp4" camera-label="Take photo for image posts" camera-media-type-id="meta-media-type" camera-media-type-value="image" />
            <div class="form-text">Choose a file for images or MP4 reels. Use the camera for image posts. Images: JPEG, PNG, or WebP (Instagram requires JPEG). Maximum upload size: 100 MB.</div>
            <div class="alert alert-light border mt-2 mb-0" id="meta-media-recommendations" role="note">
                <strong>Recommended dimensions:</strong>
                <span data-image-recommendation>Feed images: 1080 × 1350 px (4:5 portrait) or 1080 × 1080 px (1:1 square). Instagram image posts require JPEG.</span>
                <span data-video-recommendation class="d-none">Reels and TikTok videos: 1080 × 1920 px (9:16 vertical), MP4. Keep important content centered within the frame.</span>
            </div>
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
        <div class="col-12 d-none" id="tiktok-planning-notice">
            <div class="alert alert-info mb-0">
                TikTok content will be saved in your planner but will not auto-publish yet. Direct publishing requires TikTok app approval and account authorization.
            </div>
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
                <button class="btn btn-primary" id="meta-publish-now" type="submit" name="publish_now" value="1">Publish now</button>
            @endif
            <button class="btn btn-outline-primary" type="submit">{{ $submitLabel }}</button>
        </div>
    </div>
</form>
<script>
    (() => {
        const platform = document.getElementById('meta-platform');
        const format = document.getElementById('meta-media-type');
        const publishNow = document.getElementById('meta-publish-now');
        const tiktokNotice = document.getElementById('tiktok-planning-notice');
        const mediaRecommendations = document.getElementById('meta-media-recommendations');
        const imageRecommendation = document.querySelector('[data-image-recommendation]');
        const videoRecommendation = document.querySelector('[data-video-recommendation]');
        const selectedPlatforms = () => platform.multiple
            ? Array.from(platform.selectedOptions, (option) => option.value)
            : [platform.value];
        const updateFormats = () => {
            const platforms = selectedPlatforms();
            const hasInstagram = platforms.includes('instagram');
            const hasTikTok = platforms.includes('tiktok');
            const textOption = format.querySelector('option[value="text"]');
            textOption.disabled = hasInstagram;
            if (hasInstagram && format.value === 'text') {
                format.value = 'image';
            }
            if (publishNow) {
                publishNow.classList.toggle('d-none', hasTikTok);
            }
            tiktokNotice.classList.toggle('d-none', !hasTikTok);
            mediaRecommendations.classList.toggle('d-none', format.value === 'text');
            imageRecommendation.classList.toggle('d-none', format.value === 'reel');
            videoRecommendation.classList.toggle('d-none', format.value !== 'reel');
        };
        window.jQuery(platform).on('change', updateFormats);
        format.addEventListener('change', updateFormats);
        updateFormats();
    })();
</script>
