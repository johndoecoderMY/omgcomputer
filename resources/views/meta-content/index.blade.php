@extends('layouts.app')

@section('title', 'Meta Content')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Marketing" title="Meta Content"
        description="Prepare and publish posts for your Facebook Page and Instagram professional account.">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('meta-content.create') }}">
                <i class="bi bi-plus me-2" aria-hidden="true"></i>Create content
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if ($result = session('meta_result'))
        <div class="alert {{ $result['success'] ? 'alert-success' : 'alert-danger' }}" role="status">
            {{ $result['message'] }}
        </div>
    @endif

    @unless ($configured['page_access_token'] && $configured['page_id'])
        <div class="alert alert-warning">Facebook Page publishing needs <code>META_PAGE_ID</code> and a page token in <code>META_PAGE_ACCESS_TOKEN</code> (or <code>META_ACCESS_TOKEN</code>).</div>
    @endunless
    @unless ($configured['instagram_access_token'] && $configured['instagram_user_id'])
        <div class="alert alert-warning">Instagram publishing needs <code>META_INSTAGRAM_USER_ID</code> and a token in <code>META_INSTAGRAM_ACCESS_TOKEN</code> (or <code>META_ACCESS_TOKEN</code>).</div>
    @endunless
    @unless ($configured['public_media_url'])
        <div class="alert alert-warning">
            Set <code>APP_URL</code> or <code>META_PUBLIC_MEDIA_BASE_URL</code> to a publicly reachable HTTPS URL
            so Meta can fetch uploaded images and videos.
        </div>
    @endunless

    <div class="alert alert-info">
        Scheduled content is published by Laravel's scheduler. Run <code>php artisan schedule:work</code> locally
        or configure the production scheduler to run <code>php artisan schedule:run</code> every minute.
    </div>

    <div class="card p-2">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead>
                    <tr><th>Content</th><th>Platform</th><th>Type</th><th>Status</th><th>Schedule / published</th><th>Remote post</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($posts as $post)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ \Illuminate\Support\Str::limit($post->caption ?: 'Media post', 110) }}</div>
                                @if ($post->error_message)
                                    <div class="small text-danger">{{ $post->error_message }}</div>
                                @endif
                            </td>
                            <td>{{ $post->platform === 'facebook' ? 'Facebook Page' : 'Instagram' }}</td>
                            <td>{{ ucfirst($post->media_type) }}</td>
                            <td><x-status-badge :status="$post->status" /></td>
                            <td>{{ $post->published_at?->format('M j, Y g:i A') ?? $post->scheduled_at?->format('M j, Y g:i A') ?? '—' }}</td>
                            <td>{{ $post->meta_post_id ?: '—' }}</td>
                            <td class="text-nowrap">
                                @if (in_array($post->status, ['draft', 'failed'], true))
                                    <form class="d-inline" method="POST" action="{{ route('meta-content.publish', $post) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-primary" type="submit">Publish now</button>
                                    </form>
                                @endif
                                @if (in_array($post->status, ['draft', 'scheduled', 'failed'], true))
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('meta-content.edit', $post) }}">Edit</a>
                                    <form class="d-inline" method="POST" action="{{ route('meta-content.destroy', $post) }}"
                                        onsubmit="return confirm('Delete this unpublished content?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-5">No Meta content yet. Create a draft, schedule a post, or publish now.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-body">{{ $posts->links() }}</div>
    </div>
</div>
@endsection
