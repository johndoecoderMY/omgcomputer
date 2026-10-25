@extends('layouts.app')

@section('title', 'Meta Ads')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Marketing" title="Meta Ads"
        description="Connect your Meta ad account and Facebook Page to manage campaigns and Page posts.">
        <x-slot:actions>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn {{ $connection ? 'btn-outline-primary' : 'btn-primary' }} {{ $configured ? '' : 'disabled' }}"
                    href="{{ $configured ? route('meta-ads.connect') : '#' }}"
                    @unless ($configured) aria-disabled="true" tabindex="-1" @endunless>
                    <i class="bi bi-link-45deg me-2" aria-hidden="true"></i>{{ $connection ? 'Connect / change Meta assets' : 'Connect Meta' }}
                </a>
                @if ($connection)
                    <form method="POST" action="{{ route('meta-ads.disconnect') }}"
                        onsubmit="return confirm('Disconnect this Meta ad account?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline-danger" type="submit">Disconnect account</button>
                    </form>
                @endif
                @if ($pageConnection)
                    <form method="POST" action="{{ route('meta-ads.page.disconnect') }}"
                        onsubmit="return confirm('Disconnect this Facebook Page?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline-danger" type="submit">Disconnect Page</button>
                    </form>
                @endif
            </div>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if ($result = session('meta_ads_result'))
        <div class="alert {{ $result['success'] ? 'alert-success' : 'alert-danger' }}" role="status">
            {{ $result['message'] }}
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <p class="fw-bold mb-2">Please correct the following:</p>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @unless ($configured)
        <div class="alert alert-warning">
            Configure <code>META_APP_ID</code> and <code>META_APP_SECRET</code> in the server environment.
        </div>
    @endunless
    <div class="alert alert-info">
        In Meta for Developers, enable Marketing API access and register this exact OAuth redirect URI:
        <code>{{ route('meta-ads.callback') }}</code>.
        The app must have approval for <code>ads_read</code>, <code>ads_management</code>, <code>pages_show_list</code>,
        <code>pages_read_engagement</code>, and <code>pages_manage_posts</code>. The authorizing user must have access to
        the selected ad account and Page.
    </div>

    @if ($pendingAccounts || $pendingPages)
        <div class="card p-2 mb-4">
            <div class="card-header bg-body">
                <h2 class="h5 mb-0 text-primary">Choose Meta assets</h2>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @if ($pendingAccounts)
                        <div class="col-lg-6">
                            <p>Select the ad account this workspace should manage.</p>
                            <form method="POST" action="{{ route('meta-ads.account.select') }}" class="d-flex flex-wrap gap-2">
                                @csrf
                                <select class="form-select flex-grow-1" name="account_id" required>
                                    @foreach ($pendingAccounts as $account)
                                        <option value="{{ $account['id'] }}">
                                            {{ $account['name'] }} ({{ $account['account_id'] }})
                                            @if ($account['currency']) — {{ $account['currency'] }} @endif
                                        </option>
                                    @endforeach
                                </select>
                                <button class="btn btn-primary" type="submit">Connect ad account</button>
                            </form>
                        </div>
                    @endif
                    @if ($pendingPages)
                        <div class="col-lg-6">
                            <p>Select the Facebook Page to view and manage.</p>
                            <form method="POST" action="{{ route('meta-ads.page.select') }}" class="d-flex flex-wrap gap-2">
                                @csrf
                                <select class="form-select flex-grow-1" name="page_id" required>
                                    @foreach ($pendingPages as $candidatePage)
                                        <option value="{{ $candidatePage['id'] }}">
                                            {{ $candidatePage['name'] }}
                                            @if ($candidatePage['category']) — {{ $candidatePage['category'] }} @endif
                                        </option>
                                    @endforeach
                                </select>
                                <button class="btn btn-primary" type="submit">Connect Page</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if ($pageConnection)
        <div class="card p-2 mb-4">
            <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h2 class="h5 mb-1 text-primary">Facebook Page</h2>
                    <div class="fw-semibold">{{ $page['name'] ?? $pageConnection->page_name }}</div>
                    <div class="small text-body-secondary">
                        {{ $pageConnection->category ?? 'Facebook Page' }} · ID {{ $pageConnection->page_id }}
                        @if ($page && $page['fan_count'] !== null)
                            · {{ number_format($page['fan_count']) }} followers
                        @endif
                    </div>
                </div>
                @if ($page && $page['link'])
                    <a class="btn btn-sm btn-outline-primary" href="{{ $page['link'] }}" target="_blank" rel="noopener noreferrer">Open Page</a>
                @endif
            </div>
            <div class="card-body">
                @if ($pageError)
                    <div class="alert alert-danger" role="alert">{{ $pageError }}</div>
                @endif
                <form method="POST" action="{{ route('meta-ads.page.posts.store') }}" class="row g-3 mb-4">
                    @csrf
                    <div class="col-12">
                        <label class="form-label" for="page-post-message">Publish a Page post</label>
                        <textarea class="form-control" id="page-post-message" name="message" rows="3" maxlength="63206" required>{{ old('message') }}</textarea>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label" for="page-post-link">Link (optional)</label>
                        <input class="form-control" id="page-post-link" name="link" type="url" value="{{ old('link') }}" maxlength="2048" placeholder="https://example.com">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary w-100" type="submit">Publish to Page</button>
                    </div>
                </form>
                <h3 class="h6">Recent Page posts</h3>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead>
                            <tr><th>Post</th><th>Published</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($pagePosts as $pagePost)
                                <tr>
                                    <td>
                                        @if ($pagePost['permalink_url'])
                                            <a href="{{ $pagePost['permalink_url'] }}" target="_blank" rel="noopener noreferrer">
                                                {{ \Illuminate\Support\Str::limit($pagePost['message'] ?: 'Open Page post', 180) }}
                                            </a>
                                        @else
                                            {{ \Illuminate\Support\Str::limit($pagePost['message'] ?: 'Page post', 180) }}
                                        @endif
                                    </td>
                                    <td>{{ $pagePost['created_time'] ? \Illuminate\Support\Carbon::parse($pagePost['created_time'])->format('M j, Y g:i A') : '—' }}</td>
                                    <td>
                                        @if ($pagePost['message'] !== '')
                                            <form method="POST" action="{{ route('meta-ads.page.posts.update', $pagePost['id']) }}" class="d-flex flex-wrap gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <textarea class="form-control form-control-sm" name="message" rows="2" maxlength="63206" aria-label="Edit Page post text" required>{{ $pagePost['message'] }}</textarea>
                                                <button class="btn btn-sm btn-outline-primary" type="submit">Update post</button>
                                            </form>
                                            <form method="POST" action="{{ route('meta-ads.page.posts.delete', $pagePost['id']) }}" class="mt-2"
                                                onsubmit="return confirm('Delete this post from the Facebook Page? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Delete post</button>
                                            </form>
                                        @else
                                            <span class="text-body-secondary">This post cannot be edited here.</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-body-secondary py-4">No recent Page posts were returned.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="card p-2 mb-4">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="h5">Facebook Pages API</h2>
                    <p class="text-body-secondary mb-0">Connect a Page to view its details and recent posts, then publish or edit posts.</p>
                </div>
                <a class="btn btn-outline-primary {{ $configured ? '' : 'disabled' }}"
                    href="{{ $configured ? route('meta-ads.connect') : '#' }}"
                    @unless ($configured) aria-disabled="true" tabindex="-1" @endunless>Connect a Page</a>
            </div>
        </div>
    @endif

    @if ($connection)
        <div class="card p-2 mb-4">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <div class="small text-body-secondary">Connected ad account</div>
                    <div class="fw-semibold">{{ $connection->account_name }}</div>
                    <code>{{ $connection->account_id }}</code>
                </div>
                <div class="small text-body-secondary">
                    @if ($connection->token_expires_at)
                        Authorization expires {{ $connection->token_expires_at->format('M j, Y') }}
                    @else
                        Authorization expiry not provided by Meta
                    @endif
                </div>
            </div>
        </div>

        @if ($campaignError)
            <div class="alert alert-danger" role="alert">{{ $campaignError }}</div>
        @endif

        <div class="card p-2 mb-4">
            <div class="card-header bg-body">
                <h2 class="h5 mb-0 text-primary">Create campaign</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('meta-ads.campaigns.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label" for="campaign-name">Campaign name</label>
                        <input class="form-control" id="campaign-name" name="name" value="{{ old('name') }}" maxlength="255" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="campaign-objective">Objective</label>
                        <select class="form-select" id="campaign-objective" name="objective" required>
                            @foreach ($objectives as $key => $label)
                                <option value="{{ $key }}" @selected(old('objective') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100" type="submit">Create paused</button>
                    </div>
                    <div class="col-12 small text-body-secondary">
                        Campaigns are created paused. Ad sets, creatives, and budgets are managed separately in Meta Ads Manager.
                    </div>
                </form>
            </div>
        </div>

        <div class="card p-2">
            <div class="card-header bg-body">
                <h2 class="h5 mb-0 text-primary">Campaigns</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Campaign</th><th>Objective</th><th>Status</th><th>Effective status</th><th>Created</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($campaigns as $campaign)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $campaign['name'] }}</div>
                                    <div class="small text-body-secondary">{{ $campaign['id'] }}</div>
                                </td>
                                <td>{{ $objectives[$campaign['objective']] ?? str_replace('_', ' ', $campaign['objective']) }}</td>
                                <td><x-status-badge :status="strtolower($campaign['status'])" /></td>
                                <td>{{ $campaign['effective_status'] ? str_replace('_', ' ', $campaign['effective_status']) : '—' }}</td>
                                <td>{{ $campaign['created_time'] ? \Illuminate\Support\Carbon::parse($campaign['created_time'])->format('M j, Y') : '—' }}</td>
                                <td>
                                    @if (in_array($campaign['status'], ['ACTIVE', 'PAUSED'], true))
                                    <form method="POST" action="{{ route('meta-ads.campaigns.update', $campaign['id']) }}" class="d-flex flex-wrap gap-2"
                                        onsubmit="return this.elements.status.value !== 'ACTIVE' || confirm('Activating this campaign may cause it to spend money. Continue?');">
                                        @csrf
                                        @method('PATCH')
                                        <input class="form-control form-control-sm" name="name" value="{{ $campaign['name'] }}" maxlength="255" aria-label="Campaign name" required>
                                        <select class="form-select form-select-sm" name="status" aria-label="Campaign status">
                                            @foreach (['PAUSED' => 'Paused', 'ACTIVE' => 'Active'] as $key => $label)
                                                <option value="{{ $key }}" @selected($campaign['status'] === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                                    </form>
                                    @else
                                        <span class="text-body-secondary">This campaign status is read-only here.</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-body-secondary py-5">No campaigns are available for this ad account.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="card p-2">
            <div class="card-body text-center py-5">
                <h2 class="h5">No ad account connected</h2>
                <p class="text-body-secondary mb-0">Connect a Meta ad account to view and manage its campaigns here.</p>
            </div>
        </div>
    @endif
</div>
@endsection
