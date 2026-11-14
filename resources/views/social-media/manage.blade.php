@extends('layouts.app')

@section('title', 'Social Media')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Marketing" title="Social Media"
        description="Manage connected account profiles and review platform data and publishing tools.">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('meta-content.index') }}">
                <span class="bi bi-arrow-left me-2" aria-hidden="true"></span>Content Planner
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if ($result = session('social_media_result'))
        <div class="alert {{ $result['success'] ? 'alert-success' : 'alert-danger' }}" role="status">
            {{ $result['message'] }}
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <p class="fw-semibold mb-1">Please correct the following:</p>
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-xl-4">
            <section class="card h-100 p-2">
                <div class="card-header bg-body d-flex justify-content-between align-items-center gap-2">
                    <h2 class="h5 mb-0"><span class="bi bi-facebook me-2" aria-hidden="true"></span>Facebook Page</h2>
                    @if ($facebookPage)
                        <x-status-badge status="active" label="Connected" />
                    @else
                        <x-status-badge status="not_connected" label="Not connected" />
                    @endif
                </div>
                <div class="card-body">
                    @if ($facebookPage)
                        @if ($facebookError)<div class="alert alert-warning" role="alert">{{ $facebookError }}</div>@endif
                        <div class="d-flex align-items-center gap-3 mb-3">
                            @if ($facebookProfile && $facebookProfile['picture'])
                                <img src="{{ $facebookProfile['picture'] }}" alt="" class="rounded-circle" width="56" height="56">
                            @endif
                            <div>
                                <div class="fw-semibold">{{ $facebookProfile['name'] ?? $facebookPage->page_name }}</div>
                                <div class="small text-body-secondary">{{ $facebookProfile['category'] ?? $facebookPage->category ?? 'Facebook Page' }}</div>
                            </div>
                        </div>
                        <dl class="row small mb-0">
                            <dt class="col-5">Followers</dt><dd class="col-7">{{ isset($facebookProfile['fan_count']) ? number_format($facebookProfile['fan_count']) : 'Unavailable' }}</dd>
                            <dt class="col-5">About</dt><dd class="col-7">{{ $facebookProfile['about'] ?? 'Not set' }}</dd>
                            <dt class="col-5">Website</dt><dd class="col-7 text-break">{{ $facebookProfile['website'] ?? 'Not set' }}</dd>
                        </dl>
                        @if ($facebookPosts)
                            <hr>
                            <h3 class="h6">Recent Page posts</h3>
                            <ul class="list-group list-group-flush">
                                @foreach (array_slice($facebookPosts, 0, 5) as $post)
                                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2">
                                        <span class="small">{{ \Illuminate\Support\Str::limit($post['message'] ?: 'Page post', 60) }}</span>
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('meta-ads.page.posts.comments', $post['id']) }}">Manage comments</a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if ($facebookProfile && $facebookProfile['link'])
                            <a class="btn btn-sm btn-outline-secondary mt-3" href="{{ $facebookProfile['link'] }}" target="_blank" rel="noopener noreferrer">Open Page</a>
                        @endif
                        <hr>
                        <h3 class="h6">Edit Facebook Page profile</h3>
                        <form method="POST" action="{{ route('social-media.facebook.profile.update') }}" class="vstack gap-2">
                            @csrf
                            @method('PUT')
                            <div>
                                <label class="form-label" for="facebook-name">Page name</label>
                                <input class="form-control" id="facebook-name" name="name" maxlength="75" required
                                    value="{{ old('name', $facebookProfile['name'] ?? $facebookPage->page_name) }}">
                            </div>
                            <div>
                                <label class="form-label" for="facebook-about">About</label>
                                <textarea class="form-control" id="facebook-about" name="about" rows="2" maxlength="100">{{ old('about', $facebookProfile['about'] ?? '') }}</textarea>
                            </div>
                            <div>
                                <label class="form-label" for="facebook-website">Website</label>
                                <input class="form-control" id="facebook-website" name="website" type="url" maxlength="255"
                                    value="{{ old('website', $facebookProfile['website'] ?? '') }}">
                            </div>
                            @foreach (['name', 'about', 'website'] as $field)
                                @error($field)<div class="text-danger small">{{ $message }}</div>@enderror
                            @endforeach
                            <button class="btn btn-sm btn-primary align-self-start" type="submit">Save Facebook profile</button>
                        </form>
                        <form method="POST" action="{{ route('social-media.facebook.picture.update') }}" class="mt-3">
                            @csrf
                            @method('PUT')
                            <label class="form-label" for="facebook-picture-url">Profile photo URL</label>
                            <div class="input-group">
                                <input class="form-control" id="facebook-picture-url" name="picture_url" type="url" maxlength="2048"
                                    placeholder="https://example.com/profile-photo.jpg" required>
                                <button class="btn btn-outline-primary" type="submit">Update photo</button>
                            </div>
                            @error('picture_url')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </form>
                    @else
                        <p class="text-body-secondary mb-0">Connect a Facebook Page to read profile details and manage Page posts.</p>
                    @endif
                </div>
                <div class="card-footer bg-body d-flex flex-wrap gap-2">
                    @if ($facebookPage)
                        <form method="POST" action="{{ route('meta-ads.page.disconnect') }}"
                            onsubmit="return confirm('Disconnect the Facebook Page?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Disconnect Page</button>
                        </form>
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('meta-ads.index') }}">Manage Page posts</a>
                    @else
                        <a class="btn btn-sm btn-primary" href="{{ route('meta-ads.connect') }}">Connect Facebook Page</a>
                    @endif
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-4">
            <section class="card h-100 p-2">
                <div class="card-header bg-body d-flex justify-content-between align-items-center gap-2">
                    <h2 class="h5 mb-0"><span class="bi bi-instagram me-2" aria-hidden="true"></span>Instagram</h2>
                    @if ($instagramProfile)
                        <x-status-badge status="active" label="Connected" />
                    @elseif ($configured['instagram'])
                        <x-status-badge status="configured" label="Credentials configured" />
                    @else
                        <x-status-badge status="not_connected" label="Not connected" />
                    @endif
                </div>
                <div class="card-body">
                    @if ($instagramError)<div class="alert alert-warning" role="alert">{{ $instagramError }}</div>@endif
                    @if ($instagramProfile)
                        <div class="d-flex align-items-center gap-3 mb-3">
                            @if ($instagramProfile['profile_picture_url'] ?? null)
                                <img src="{{ $instagramProfile['profile_picture_url'] }}" alt="" class="rounded-circle" width="56" height="56">
                            @endif
                            <div>
                                <div class="fw-semibold">{{ $instagramProfile['name'] ?: '@'.$instagramProfile['username'] }}</div>
                                <div class="small text-body-secondary">{{ '@'.$instagramProfile['username'] }}</div>
                            </div>
                        </div>
                        <dl class="row small mb-0">
                            <dt class="col-6">Followers</dt><dd class="col-6">{{ isset($instagramProfile['followers_count']) ? number_format($instagramProfile['followers_count']) : 'Unavailable' }}</dd>
                            <dt class="col-6">Following</dt><dd class="col-6">{{ isset($instagramProfile['follows_count']) ? number_format($instagramProfile['follows_count']) : 'Unavailable' }}</dd>
                            <dt class="col-6">Posts</dt><dd class="col-6">{{ isset($instagramProfile['media_count']) ? number_format($instagramProfile['media_count']) : 'Unavailable' }}</dd>
                            <dt class="col-6">Bio</dt><dd class="col-6">{{ $instagramProfile['biography'] ?: 'Not set' }}</dd>
                            <dt class="col-6">Website</dt><dd class="col-6 text-break">{{ $instagramProfile['website'] ?: 'Not set' }}</dd>
                        </dl>
                        <p class="small text-body-secondary mt-3">Instagram’s API does not support editing profile details. Reading profiles, publishing, and comment moderation require approved Meta scopes.</p>
                        @if ($instagramMediaError)
                            <div class="alert alert-warning" role="alert">{{ $instagramMediaError }}</div>
                        @endif
                        @if ($instagramMedia)
                            <h3 class="h6">Recent media</h3>
                            <ul class="list-group list-group-flush">
                                @foreach (array_slice($instagramMedia, 0, 5) as $media)
                                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2">
                                        <div class="small">
                                            @if ($media['permalink'])
                                                <a href="{{ $media['permalink'] }}" target="_blank" rel="noopener noreferrer">
                                                    {{ \Illuminate\Support\Str::limit($media['caption'] ?: ($media['media_type'] ?? 'Instagram post'), 60) }}
                                                </a>
                                            @else
                                                {{ \Illuminate\Support\Str::limit($media['caption'] ?: ($media['media_type'] ?? 'Instagram post'), 60) }}
                                            @endif
                                            <div class="text-body-secondary">{{ $media['comments_count'] ?? 0 }} comments</div>
                                        </div>
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('social-media.instagram.comments', $media['id']) }}">Manage comments</a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    @elseif ($configured['instagram'])
                        <p class="mb-0 text-body-secondary">Credentials are configured, but profile details could not be read. Check <code>META_INSTAGRAM_USER_ID</code>, the Meta app, and granted permissions.</p>
                    @else
                        <p class="mb-0 text-body-secondary">Configure <code>META_INSTAGRAM_USER_ID</code> and <code>META_INSTAGRAM_ACCESS_TOKEN</code> for a professional account to read profile details.</p>
                    @endif
                </div>
                <div class="card-footer bg-body d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('meta-content.index') }}">Manage Instagram posts</a>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-4">
            <section class="card h-100 p-2">
                <div class="card-header bg-body d-flex justify-content-between align-items-center gap-2">
                    <h2 class="h5 mb-0"><span class="bi bi-tiktok me-2" aria-hidden="true"></span>TikTok</h2>
                    @if ($tiktokConnection)
                        <x-status-badge status="active" label="Connected" />
                    @else
                        <x-status-badge status="not_connected" label="Account not connected" />
                    @endif
                </div>
                <div class="card-body">
                    @if ($tiktokError)<div class="alert alert-warning" role="alert">{{ $tiktokError }}</div>@endif
                    @if ($tiktokProfile)
                        <div class="d-flex align-items-center gap-3 mb-3">
                            @if ($tiktokProfile['avatar_url'] ?? null)
                                <img src="{{ $tiktokProfile['avatar_url'] }}" alt="" class="rounded-circle" width="56" height="56">
                            @endif
                            <div>
                                <div class="fw-semibold">{{ $tiktokProfile['display_name'] ?? $tiktokProfile['username'] ?? 'TikTok profile' }}</div>
                                @if ($tiktokProfile['username'] ?? null)<div class="small text-body-secondary">{{ '@'.$tiktokProfile['username'] }}</div>@endif
                            </div>
                        </div>
                        <dl class="row small mb-0">
                            <dt class="col-6">Followers</dt><dd class="col-6">{{ isset($tiktokProfile['follower_count']) ? number_format($tiktokProfile['follower_count']) : 'Unavailable' }}</dd>
                            <dt class="col-6">Following</dt><dd class="col-6">{{ isset($tiktokProfile['following_count']) ? number_format($tiktokProfile['following_count']) : 'Unavailable' }}</dd>
                            <dt class="col-6">Likes</dt><dd class="col-6">{{ isset($tiktokProfile['likes_count']) ? number_format($tiktokProfile['likes_count']) : 'Unavailable' }}</dd>
                            <dt class="col-6">Videos</dt><dd class="col-6">{{ isset($tiktokProfile['video_count']) ? number_format($tiktokProfile['video_count']) : 'Unavailable' }}</dd>
                            <dt class="col-6">Bio</dt><dd class="col-6">{{ $tiktokProfile['bio_description'] ?? 'Not set' }}</dd>
                        </dl>
                        <p class="small text-body-secondary mt-3 mb-0">TikTok’s Display API is read-only. Profile editing and comment management are not available through this API.</p>
                    @else
                        <p class="mb-0 text-body-secondary">TikTok’s API provides profile details and follower statistics after account authorization. Editing profile fields and managing comments are not supported.</p>
                        @if ($configured['tiktok'])
                            <p class="small text-body-secondary mt-2 mb-0">Register this exact redirect URI in the TikTok Developer Portal and set it as <code>TIKTOK_REDIRECT_URI</code>: <code>{{ route('social-media.tiktok.callback') }}</code></p>
                        @endif
                    @endif
                </div>
                <div class="card-footer bg-body d-flex flex-wrap gap-2">
                    @if ($tiktokConnection)
                        <form method="POST" action="{{ route('social-media.tiktok.disconnect') }}"
                            onsubmit="return confirm('Disconnect the TikTok account and revoke authorization?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Disconnect TikTok</button>
                        </form>
                    @elseif ($configured['tiktok'])
                        <a class="btn btn-sm btn-primary" href="{{ route('social-media.tiktok.connect') }}">Connect TikTok</a>
                    @else
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('marketing.tiktok') }}">Configure TikTok</a>
                    @endif
                </div>
            </section>
        </div>
    </div>

    <div class="card p-2 mt-3">
        <div class="card-header bg-body">
            <h2 class="h5 mb-1">Website profile links</h2>
            <p class="small text-body-secondary mb-0">These links appear in the public footer; they do not edit the connected platform profiles.</p>
        </div>
        <form method="POST" action="{{ route('social-media.profiles.update') }}">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="facebook-url">Facebook link</label>
                        <input class="form-control" id="facebook-url" name="facebook_url" type="url" maxlength="255"
                            value="{{ old('facebook_url', $companyProfile->facebook_url) }}" placeholder="https://www.facebook.com/your-page">
                        @error('facebook_url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="instagram-url">Instagram link</label>
                        <input class="form-control" id="instagram-url" name="instagram_url" type="url" maxlength="255"
                            value="{{ old('instagram_url', $companyProfile->instagram_url) }}" placeholder="https://www.instagram.com/your-profile/">
                        @error('instagram_url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="tiktok-url">TikTok link</label>
                        <input class="form-control" id="tiktok-url" name="tiktok_url" type="url" maxlength="255"
                            value="{{ old('tiktok_url', $companyProfile->tiktok_url) }}" placeholder="https://www.tiktok.com/@your-profile">
                        @error('tiktok_url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="card-footer bg-body text-end">
                <button class="btn btn-outline-primary" type="submit"><span class="bi bi-save me-2" aria-hidden="true"></span>Save website links</button>
            </div>
        </form>
    </div>
</div>
@endsection
