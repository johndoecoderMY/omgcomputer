@extends('layouts.app')

@section('title', 'Instagram Comments')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Marketing" title="Instagram comments"
        description="Reply to comments or hide and delete comments on your Instagram media."
        :back-url="route('social-media.index')" back-label="Social Media" />

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if ($result = session('social_media_result'))
        <div class="alert {{ $result['success'] ? 'alert-success' : 'alert-danger' }}" role="status">
            {{ $result['message'] }}
        </div>
    @endif
    @if ($commentsError)
        <div class="alert alert-danger" role="alert">{{ $commentsError }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="card p-2">
        <div class="card-header bg-body d-flex justify-content-between align-items-center gap-2">
            <h2 class="h5 mb-0">Comments</h2>
            <span class="small text-body-secondary">Media ID {{ $mediaId }}</span>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                Instagram comment moderation requires Meta approval for <code>instagram_manage_comments</code>. Only the latest 50 comments are loaded.
            </div>
            @forelse ($comments as $comment)
                <article class="border rounded-2 p-3 mb-3">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <div class="fw-semibold">{{ '@'.$comment['username'] }}</div>
                            <div class="small text-body-secondary">
                                {{ $comment['timestamp'] ? \Illuminate\Support\Carbon::parse($comment['timestamp'])->format('M j, Y g:i A') : '' }}
                                @if ($comment['hidden']) · Hidden @endif
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <form method="POST" action="{{ route('social-media.instagram.comments.visibility', [$mediaId, $comment['id']]) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="hidden" value="{{ $comment['hidden'] ? '0' : '1' }}">
                                <button class="btn btn-sm btn-outline-secondary" type="submit">{{ $comment['hidden'] ? 'Unhide' : 'Hide' }}</button>
                            </form>
                            <form method="POST" action="{{ route('social-media.instagram.comments.delete', [$mediaId, $comment['id']]) }}"
                                onsubmit="return confirm('Delete this comment from Instagram?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                            </form>
                        </div>
                    </div>
                    <p class="my-3 text-break">{{ $comment['message'] }}</p>
                    <form method="POST" action="{{ route('social-media.instagram.comments.reply', [$mediaId, $comment['id']]) }}"
                        class="d-flex flex-column flex-sm-row gap-2">
                        @csrf
                        <label class="visually-hidden" for="reply-{{ $comment['id'] }}">Reply to {{ $comment['username'] }}</label>
                        <input class="form-control" id="reply-{{ $comment['id'] }}" name="message" maxlength="2200" required placeholder="Write a reply">
                        <button class="btn btn-sm btn-primary" type="submit">Reply</button>
                    </form>
                </article>
            @empty
                @unless ($commentsError)
                    <p class="text-body-secondary mb-0">No comments were returned for this media.</p>
                @endunless
            @endforelse
        </div>
    </div>
</div>
@endsection
