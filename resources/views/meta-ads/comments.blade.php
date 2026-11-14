@extends('layouts.app')

@section('title', 'Page Comments')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Marketing" title="Page comments"
        :description="$pageName.' · Post '.$postId"
        :back-url="route('meta-ads.index')" back-label="Facebook Page" />

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if ($message = session('comments_error'))
        <div class="alert alert-danger" role="alert">{{ $message }}</div>
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
            <span class="small text-body-secondary">Up to 100 recent comments</span>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                Comment access requires Meta approval for <code>pages_read_user_content</code> and <code>pages_manage_engagement</code>.
            </div>
            @forelse ($comments as $comment)
                <article class="border rounded-2 p-3 mb-3">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <div class="fw-semibold">{{ $comment['author'] }}</div>
                            <div class="small text-body-secondary">{{ $comment['created_time'] ? \Illuminate\Support\Carbon::parse($comment['created_time'])->format('M j, Y g:i A') : '' }}</div>
                        </div>
                        <form method="POST" action="{{ route('meta-ads.page.comments.delete', [$postId, $comment['id']]) }}"
                            onsubmit="return confirm('Delete this comment from Facebook?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                        </form>
                    </div>
                    <p class="my-3 text-break">{{ $comment['message'] }}</p>
                    <form method="POST" action="{{ route('meta-ads.page.comments.reply', [$postId, $comment['id']]) }}"
                        class="d-flex flex-column flex-sm-row gap-2">
                        @csrf
                        <label class="visually-hidden" for="reply-{{ $comment['id'] }}">Reply to {{ $comment['author'] }}</label>
                        <input class="form-control" id="reply-{{ $comment['id'] }}" name="message" maxlength="8000" required placeholder="Write a reply">
                        <button class="btn btn-sm btn-primary" type="submit">Reply</button>
                    </form>
                </article>
            @empty
                @unless ($commentsError)
                    <p class="text-body-secondary mb-0">No comments were returned for this post.</p>
                @endunless
            @endforelse
        </div>
    </div>
</div>
@endsection
