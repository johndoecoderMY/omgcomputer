<section class="project-quotation-terms">
    <h2 class="h5">Project scope and terms</h2>
    <dl class="mb-2">
        <dt>Included scope</dt>
        <dd>{{ $document->project_scope ?: 'As described by the quotation line items. Confirm any additional requirements in writing before work begins.' }}</dd>

        <dt>Revisions</dt>
        <dd>
            Up to {{ $document->included_revision_rounds }} revision rounds are included. Each round is one consolidated set of feedback on the agreed scope.
            Additional rounds or changes to the agreed scope may be quoted separately and may affect the schedule.
        </dd>

        <dt>Target completion</dt>
        <dd>
            {{ $document->project_due_date?->format('F j, Y') ?? 'To be agreed in writing after quotation acceptance.' }}
            The schedule begins after any agreed initial payment and receipt of the required content, approvals, and access.
            Delays in these items may move the target date.
        </dd>

        <dt>Payment schedule</dt>
        <dd>{{ $document->payment_terms ?: 'To be agreed in writing before work begins.' }}</dd>
    </dl>
    <p class="mb-0">
        Hosting, domain registration, third-party services, and ongoing maintenance are excluded unless listed in the quotation.
        Final website handover is provided after the agreed balance is paid. Acceptance confirms approval of this scope and these terms.
    </p>
</section>
