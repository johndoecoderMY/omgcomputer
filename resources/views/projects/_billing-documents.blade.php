<div class="card project-panel mb-4" aria-labelledby="project-billing-heading">
    <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h5 mb-0" id="project-billing-heading">Project billing and quotations</h2>
            <p class="small text-body-secondary mb-0">Invoices and quotations linked to this project. Quotations can be marked sent, accepted, or rejected from their detail page.</p>
        </div>
        @if ($canManageBilling)
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-sm btn-outline-primary" href="{{ route('billing.create', ['type' => 'quotation', 'project_id' => $project->id]) }}">
                    <i class="bi bi-file-text me-1" aria-hidden="true"></i>Create quotation
                </a>
                <a class="btn btn-sm btn-primary" href="{{ route('billing.create', ['type' => 'invoice', 'project_id' => $project->id]) }}">
                    <i class="bi bi-file-earmark-spreadsheet me-1" aria-hidden="true"></i>Create invoice
                </a>
            </div>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-bordered">
            <thead>
                <tr><th>Document</th><th>Type</th><th>Issue date</th><th>Status</th><th class="text-end">Total</th></tr>
            </thead>
            <tbody>
                @forelse ($billingDocuments as $document)
                    <tr>
                        <td><a class="fw-semibold" href="{{ route('billing.documents.show', $document) }}">{{ $document->number }}</a></td>
                        <td>{{ ucfirst($document->type) }}</td>
                        <td>{{ $document->issue_date?->format('M j, Y') ?? '—' }}</td>
                        <td><x-status-badge :status="$document->status" :label="str_replace('_', ' ', ucfirst($document->status))" /></td>
                        <td class="text-end">RM {{ number_format((float) $document->total, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-body-secondary py-4">No billing documents or quotations are linked to this project yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
