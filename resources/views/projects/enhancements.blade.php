@extends('layouts.app')

@section('title', 'Project workspace · '.$project->name)

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Project workspace" :title="$project->name"
        description="Templates, approvals, documents, checklists, risks, retention, and profitability."
        :breadcrumbs="[['label' => $project->name, 'url' => route('projects.show', $project)], ['label' => 'Project workspace']]"
        :back-url="route('projects.show', $project)" back-label="Back to project" />

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3 mb-4">
        @foreach ([
            ['Actual revenue', 'RM '.number_format($profitability['actualRevenue'], 2), 'text-success'],
            ['Actual cost', 'RM '.number_format($profitability['actualCost'], 2), 'text-danger'],
            ['Margin', 'RM '.number_format($profitability['margin'], 2), $profitability['margin'] >= 0 ? 'text-success' : 'text-danger'],
            ['Logged hours', number_format($profitability['hours'], 2), 'text-primary'],
        ] as [$label, $value, $color])
            <div class="col-6 col-lg-3">
                <div class="card h-100"><div class="card-body">
                    <div class="small text-body-secondary">{{ $label }}</div>
                    <div class="h4 mb-0 {{ $color }}">{{ $value }}</div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Templates</h2>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#template-create-modal">New template</button>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($templates as $template)
                        <div class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                <strong>{{ $template->name }}</strong>
                                <span class="badge text-bg-light border ms-1">{{ str_replace('_', ' ', ucfirst($template->category)) }}</span>
                                @if ($template->description)<div class="small text-body-secondary">{{ $template->description }}</div>@endif
                            </div>
                            <form method="POST" action="{{ route('projects.enhancements.templates.apply', [$project, $template]) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-primary">Apply to project</button>
                            </form>
                        </div>
                    @empty
                        <p class="list-group-item text-body-secondary mb-0">No project templates have been created.</p>
                    @endforelse
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Client approvals</h2>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#approval-create-modal">Request approval</button>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($project->approvals as $approval)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between gap-2">
                                <div><strong>{{ $approval->title }}</strong><div class="small text-body-secondary">{{ ucfirst(str_replace('_', ' ', $approval->type)) }}</div></div>
                                <span class="badge text-bg-light border">{{ str_replace('_', ' ', ucfirst($approval->status)) }}</span>
                            </div>
                            @if ($approval->description)<p class="mt-2 mb-1">{{ $approval->description }}</p>@endif
                            @if ($approval->customer_response)<p class="small mb-0"><strong>Response:</strong> {{ $approval->customer_response }}</p>@endif
                            @if ($approval->status === 'pending')
                                <form class="row g-2 mt-2" method="POST" action="{{ route('projects.enhancements.approvals.respond', [$project, $approval]) }}">
                                    @csrf @method('PATCH')
                                    <div class="col-md-4"><select class="form-select form-select-sm" name="status"><option value="approved">Approve</option><option value="changes_requested">Request changes</option><option value="rejected">Reject</option></select></div>
                                    <div class="col-md-6"><input class="form-control form-control-sm" name="customer_response" placeholder="Internal/client response"></div>
                                    <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100">Save</button></div>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="list-group-item text-body-secondary mb-0">No approval requests yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Project documents</h2>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#document-upload-modal">Upload document</button>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($project->projectDocuments as $document)
                        <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <strong>{{ $document->title }}</strong>
                                <span class="badge text-bg-light border ms-1">{{ ucfirst($document->category) }}</span>
                                @if ($document->visible_to_customer)<span class="badge text-bg-success ms-1">Customer visible</span>@endif
                            </div>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('projects.enhancements.documents.download', [$project, $document]) }}">Download</a>
                        </div>
                    @empty
                        <p class="list-group-item text-body-secondary mb-0">No project documents uploaded.</p>
                    @endforelse
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Delivery checklists</h2>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#checklist-create-modal">New checklist</button>
                </div>
                <div class="card-body">
                    @forelse ($project->checklists as $checklist)
                        <h3 class="h6">{{ $checklist->title }}</h3>
                        @foreach ($checklist->items as $item)
                            <form method="POST" action="{{ route('projects.enhancements.checklists.toggle', [$project, $item]) }}" class="mb-2">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm {{ $item->is_completed ? 'btn-success' : 'btn-outline-secondary' }}">
                                    <i class="bi {{ $item->is_completed ? 'bi-check-square' : 'bi-square' }} me-1"></i>{{ $item->title }}
                                </button>
                            </form>
                        @endforeach
                    @empty
                        <p class="text-body-secondary mb-0">No delivery checklists yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Risks &amp; issues</h2>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#risk-create-modal">Add risk</button>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($project->risks as $risk)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between gap-2">
                                <strong>{{ $risk->title }}</strong>
                                <span class="badge text-bg-light border">{{ ucfirst($risk->status) }}</span>
                            </div>
                            <div class="small text-body-secondary">Probability: {{ ucfirst($risk->probability) }} · Impact: {{ ucfirst($risk->impact) }}</div>
                            @if ($risk->mitigation)<p class="mb-1 mt-2">{{ $risk->mitigation }}</p>@endif
                            <form method="POST" action="{{ route('projects.enhancements.risks.update', [$project, $risk]) }}" class="row g-2 mt-2">
                                @csrf @method('PATCH')
                                <div class="col-md-4"><select class="form-select form-select-sm" name="status">@foreach (['open','mitigating','resolved','accepted'] as $status)<option value="{{ $status }}" @selected($risk->status === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
                                <div class="col-md-6"><input class="form-control form-control-sm" name="mitigation" value="{{ $risk->mitigation }}" placeholder="Mitigation"></div>
                                <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100">Update</button></div>
                            </form>
                        </div>
                    @empty
                        <p class="list-group-item text-body-secondary mb-0">No project risks recorded.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between"><h2 class="h5 mb-0">Profitability</h2></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-1"><span>Quoted revenue</span><strong>RM {{ number_format($profitability['quotedRevenue'], 2) }}</strong></div>
                    <div class="d-flex justify-content-between py-1"><span>Inventory cost</span><strong>RM {{ number_format($profitability['inventoryCost'], 2) }}</strong></div>
                    <div class="d-flex justify-content-between py-1"><span>Other costs</span><strong>RM {{ number_format($profitability['costEntries'], 2) }}</strong></div>
                    <div class="d-flex justify-content-between py-1"><span>Time cost</span><strong>RM {{ number_format($profitability['timeCost'], 2) }}</strong></div>
                    <hr>
                    <div class="d-flex justify-content-between"><span>Margin %</span><strong>{{ $profitability['marginPercent'] === null ? '—' : $profitability['marginPercent'].'%' }}</strong></div>
                    <form method="POST" action="{{ route('projects.enhancements.time.store', $project) }}" class="mt-3 border-top pt-3">
                        @csrf
                        <input class="form-control form-control-sm mb-2" type="date" name="worked_on" value="{{ now()->format('Y-m-d') }}" required>
                        <div class="row g-2 mb-2"><div class="col"><input class="form-control form-control-sm" type="number" step="0.01" name="hours" placeholder="Hours" required></div><div class="col"><input class="form-control form-control-sm" type="number" step="0.01" name="hourly_rate" placeholder="Rate" value="0"></div></div>
                        <input class="form-control form-control-sm mb-2" name="notes" placeholder="Work notes">
                        <button class="btn btn-sm btn-outline-primary w-100">Add time</button>
                    </form>
                    <form method="POST" action="{{ route('projects.enhancements.cost.store', $project) }}" class="mt-3 border-top pt-3">
                        @csrf
                        <input class="form-control form-control-sm mb-2" name="title" placeholder="Cost description" required>
                        <div class="row g-2 mb-2"><div class="col"><select class="form-select form-select-sm" name="category">@foreach (['labor','hardware','software','travel','other'] as $category)<option value="{{ $category }}">{{ ucfirst($category) }}</option>@endforeach</select></div><div class="col"><input class="form-control form-control-sm" type="number" step="0.01" name="amount" placeholder="Amount" required></div></div>
                        <input class="form-control form-control-sm mb-2" type="date" name="incurred_on" value="{{ now()->format('Y-m-d') }}" required>
                        <button class="btn btn-sm btn-outline-primary w-100">Add cost</button>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h2 class="h5 mb-0">Retention policy</h2></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('projects.enhancements.retention.update', $project) }}">
                        @csrf @method('PUT')
                        <label class="form-label">Retention days</label>
                        <input class="form-control mb-2" type="number" name="retention_days" min="1" value="{{ $project->retentionPolicy?->retention_days }}">
                        <label class="form-label">After retention</label>
                        <select class="form-select mb-2" name="action"><option value="archive" @selected(($project->retentionPolicy?->action ?? 'archive') === 'archive')>Archive</option><option value="delete" @selected(($project->retentionPolicy?->action ?? '') === 'delete')>Delete</option></select>
                        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="legal_hold" value="1" id="legal-hold" @checked($project->retentionPolicy?->legal_hold)><label class="form-check-label" for="legal-hold">Legal hold</label></div>
                        <button class="btn btn-primary w-100">Save retention policy</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="h5 mb-0">Customer feedback</h2></div>
                <div class="card-body">
                    @forelse ($project->feedback as $feedback)
                        <div class="fw-semibold">{{ $feedback->rating }}/5 <span class="text-warning">{{ str_repeat('★', $feedback->rating) }}</span></div>
                        @if ($feedback->comment)<p class="mb-0 mt-2">{{ $feedback->comment }}</p>@endif
                    @empty
                        <p class="text-body-secondary mb-0">No customer feedback yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="template-create-modal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<form method="POST" action="{{ route('projects.enhancements.templates.store') }}">@csrf
<div class="modal-header"><h2 class="modal-title h5">Create project template</h2><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body row g-3">
<div class="col-md-8"><label class="form-label">Name</label><input class="form-control" name="name" required></div>
<div class="col-md-4"><label class="form-label">Category</label><select class="form-select" name="category">@foreach (['pc_build'=>'PC build','repair'=>'Repair','website'=>'Website','fyp'=>'FYP','other'=>'Other'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
<div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2"></textarea></div>
<div class="col-md-4"><label class="form-label">Objectives</label><textarea class="form-control" name="objectives" rows="4"></textarea></div>
<div class="col-md-4"><label class="form-label">Scope in</label><textarea class="form-control" name="scope_in" rows="4"></textarea></div>
<div class="col-md-4"><label class="form-label">Scope out</label><textarea class="form-control" name="scope_out" rows="4"></textarea></div>
<div class="col-md-4"><label class="form-label">Modules (one per line)</label><textarea class="form-control" name="modules" rows="5"></textarea></div>
<div class="col-md-4"><label class="form-label">Milestones (one per line)</label><textarea class="form-control" name="milestones" rows="5"></textarea></div>
<div class="col-md-4"><label class="form-label">Checklist items (one per line)</label><textarea class="form-control" name="checklist_items" rows="5"></textarea></div>
</div><div class="modal-footer"><button class="btn btn-primary">Create template</button></div></form></div></div></div>

<div class="modal fade" id="approval-create-modal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="{{ route('projects.enhancements.approvals.store', $project) }}">@csrf
<div class="modal-header"><h2 class="modal-title h5">Request client approval</h2><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<label class="form-label">Approval type</label><select class="form-select mb-3" name="type">@foreach (['quotation','requirement','milestone','deliverable'] as $type)<option value="{{ $type }}">{{ ucfirst($type) }}</option>@endforeach</select>
<label class="form-label">Title</label><input class="form-control mb-3" name="title" required>
<label class="form-label">Description</label><textarea class="form-control" name="description" rows="4"></textarea></div>
<div class="modal-footer"><button class="btn btn-primary">Add approval request</button></div></form></div></div></div>

<div class="modal fade" id="document-upload-modal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="{{ route('projects.enhancements.documents.store', $project) }}" enctype="multipart/form-data">@csrf
<div class="modal-header"><h2 class="modal-title h5">Upload project document</h2><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<label class="form-label">Title</label><input class="form-control mb-3" name="title" required>
<label class="form-label">Category</label><select class="form-select mb-3" name="category">@foreach (['contract','drawing','handover','warranty','meeting','other'] as $category)<option value="{{ $category }}">{{ ucfirst($category) }}</option>@endforeach</select>
<div class="row g-2 mb-3"><div class="col"><label class="form-label">Document date</label><input class="form-control" type="date" name="document_date"></div><div class="col"><label class="form-label">Expires</label><input class="form-control" type="date" name="expires_at"></div></div>
<div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="visible_to_customer" value="1" id="document-visible"><label class="form-check-label" for="document-visible">Visible to customer</label></div>
<label class="form-label">File</label><input class="form-control" type="file" name="file" required></div>
<div class="modal-footer"><button class="btn btn-primary">Upload</button></div></form></div></div></div>

<div class="modal fade" id="checklist-create-modal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="{{ route('projects.enhancements.checklists.store', $project) }}">@csrf
<div class="modal-header"><h2 class="modal-title h5">Create delivery checklist</h2><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><label class="form-label">Title</label><input class="form-control mb-3" name="title" required><label class="form-label">Items (one per line)</label><textarea class="form-control" name="items" rows="8"></textarea></div>
<div class="modal-footer"><button class="btn btn-primary">Create checklist</button></div></form></div></div></div>

<div class="modal fade" id="risk-create-modal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="{{ route('projects.enhancements.risks.store', $project) }}">@csrf
<div class="modal-header"><h2 class="modal-title h5">Add project risk</h2><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><label class="form-label">Title</label><input class="form-control mb-3" name="title" required>
<div class="row g-2 mb-3"><div class="col"><label class="form-label">Probability</label><select class="form-select" name="probability">@foreach(['low','medium','high'] as $value)<option value="{{ $value }}">{{ ucfirst($value) }}</option>@endforeach</select></div><div class="col"><label class="form-label">Impact</label><select class="form-select" name="impact">@foreach(['low','medium','high'] as $value)<option value="{{ $value }}">{{ ucfirst($value) }}</option>@endforeach</select></div></div>
<label class="form-label">Status</label><select class="form-select mb-3" name="status">@foreach(['open','mitigating','resolved','accepted'] as $value)<option value="{{ $value }}">{{ ucfirst($value) }}</option>@endforeach</select>
<label class="form-label">Mitigation</label><textarea class="form-control mb-3" name="mitigation" rows="3"></textarea>
<label class="form-label">Due date</label><input class="form-control" type="date" name="due_on"></div>
<div class="modal-footer"><button class="btn btn-primary">Add risk</button></div></form></div></div></div>
@endsection
