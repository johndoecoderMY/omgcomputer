<section class="project-quotation-terms">
    <h2 class="h5">{{ __('billing.project_terms.title') }}</h2>
    <dl class="mb-2">
        <dt>{{ __('billing.project_terms.included_scope') }}</dt>
        <dd>{{ $document->project_scope ?: __('billing.project_terms.scope_default') }}</dd>

        <dt>{{ __('billing.project_terms.revisions') }}</dt>
        <dd>
            {{ __('billing.project_terms.revisions_text', ['count' => $document->included_revision_rounds]) }}
        </dd>

        <dt>{{ __('billing.project_terms.target_completion') }}</dt>
        <dd>
            {{ $document->project_due_date?->locale(app()->getLocale())->translatedFormat('F j, Y') ?? __('billing.project_terms.target_default') }}
            {{ __('billing.project_terms.schedule_start') }}
        </dd>

        <dt>{{ __('billing.project_terms.payment_schedule') }}</dt>
        <dd>{{ $document->payment_terms ?: __('billing.project_terms.payment_default') }}</dd>
    </dl>
    <p class="mb-0">{{ __('billing.project_terms.handover_terms') }}</p>
</section>
