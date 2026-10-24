<div class="card project-panel mb-4" aria-labelledby="project-links-heading">
    <div class="card-header bg-body">
        <h2 class="h5 mb-0" id="project-links-heading">Project links</h2>
        <p class="small text-body-secondary mb-0">Save GitHub, staging, documentation, and other useful project URLs.</p>
    </div>
    <div class="card-body">
        <form class="row g-2 align-items-end mb-4" method="POST" action="{{ route('projects.links.store', $project) }}">
            @csrf
            <div class="col-md-3">
                <label class="form-label" for="project-link-label">Link name</label>
                <input class="form-control" id="project-link-label" name="label" value="{{ old('label') }}"
                    maxlength="120" placeholder="GitHub repository" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="project-link-url">URL</label>
                <input class="form-control" id="project-link-url" name="url" type="url" value="{{ old('url') }}"
                    maxlength="2048" placeholder="https://github.com/…" required>
            </div>
            <div class="col-md-2">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="project-link-visible" name="visible_to_customer" value="1">
                    <label class="form-check-label" for="project-link-visible">Show to customers</label>
                </div>
            </div>
            <div class="col-md-1 d-grid">
                <button class="btn btn-primary" type="submit" aria-label="Add project link"><i class="fas fa-plus" aria-hidden="true"></i></button>
            </div>
        </form>

        @error('label')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
        @error('url')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
        @error('visible_to_customer')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

        @forelse ($project->links as $link)
            <details class="border rounded p-3 mb-2">
                <summary class="d-flex flex-wrap justify-content-between gap-2">
                    <span>
                        <span class="fw-semibold">{{ $link->label }}</span>
                        <span class="small text-body-secondary ms-2">{{ $link->visible_to_customer ? 'Visible to customers' : 'Staff only' }}</span>
                    </span>
                    <span class="small text-body-secondary text-break">{{ $link->url }}</span>
                </summary>
                <div class="row g-3 mt-1">
                    <div class="col-lg-9">
                        <form class="row g-2 align-items-end" method="POST" action="{{ route('projects.links.update', [$project, $link->id]) }}">
                            @csrf
                            @method('PUT')
                            <div class="col-md-3">
                                <label class="form-label" for="link-label-{{ $link->id }}">Link name</label>
                                <input class="form-control" id="link-label-{{ $link->id }}" name="label" value="{{ $link->label }}" maxlength="120" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="link-url-{{ $link->id }}">URL</label>
                                <input class="form-control" id="link-url-{{ $link->id }}" name="url" type="url" value="{{ $link->url }}" maxlength="2048" required>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="link-visible-{{ $link->id }}"
                                        name="visible_to_customer" value="1" @checked($link->visible_to_customer)>
                                    <label class="form-check-label" for="link-visible-{{ $link->id }}">Show to customers</label>
                                </div>
                                <button class="btn btn-sm btn-outline-primary" type="submit">Save link</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-3 d-flex align-items-end">
                        <form method="POST" action="{{ route('projects.links.destroy', [$project, $link->id]) }}"
                            onsubmit="return confirm('Remove this project link?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Remove link</button>
                        </form>
                    </div>
                </div>
            </details>
        @empty
            <p class="text-body-secondary mb-0">No project links have been added.</p>
        @endforelse
    </div>
</div>
