<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectApproval;
use App\Models\ProjectChecklistItem;
use App\Models\ProjectCostEntry;
use App\Models\ProjectDocument;
use App\Models\ProjectRisk;
use App\Models\ProjectTemplate;
use App\Models\ProjectTimeEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectEnhancementController extends Controller
{
    public function show(Request $request, Project $project): View
    {
        $project->load([
            'approvals',
            'projectDocuments.uploader',
            'feedback',
            'checklists.items',
            'risks.owner',
            'retentionPolicy',
            'timeEntries.user',
            'costEntries',
            'inventoryParts',
            'billingDocuments',
        ]);

        $inventoryCost = $project->inventoryParts->sum(
            fn ($part): float => (float) $part->pivot->quantity * (float) ($part->cost_price ?? 0),
        );
        $costEntries = (float) $project->costEntries->sum('amount');
        $timeCost = (float) $project->timeEntries->sum(
            fn (ProjectTimeEntry $entry): float => (float) $entry->hours * (float) $entry->hourly_rate,
        );
        $actualCost = $inventoryCost + $costEntries + $timeCost;
        $actualRevenue = (float) $project->billingDocuments->sum('paid_amount');
        $quotedRevenue = (float) $project->billingDocuments->sum('total');
        $margin = $actualRevenue - $actualCost;

        return view('projects.enhancements', [
            'project' => $project,
            'templates' => ProjectTemplate::where('is_active', true)->latest()->get(),
            'profitability' => [
                'actualCost' => $actualCost,
                'inventoryCost' => $inventoryCost,
                'costEntries' => $costEntries,
                'timeCost' => $timeCost,
                'actualRevenue' => $actualRevenue,
                'quotedRevenue' => $quotedRevenue,
                'margin' => $margin,
                'marginPercent' => $actualRevenue > 0 ? round($margin / $actualRevenue * 100, 2) : null,
                'hours' => (float) $project->timeEntries->sum('hours'),
            ],
        ]);
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'category' => ['required', Rule::in(['pc_build', 'repair', 'website', 'fyp', 'other'])],
            'description' => ['nullable', 'string', 'max:5000'],
            'objectives' => ['nullable', 'string', 'max:5000'],
            'scope_in' => ['nullable', 'string', 'max:5000'],
            'scope_out' => ['nullable', 'string', 'max:5000'],
            'modules' => ['nullable', 'string', 'max:5000'],
            'milestones' => ['nullable', 'string', 'max:5000'],
            'checklist_items' => ['nullable', 'string', 'max:5000'],
        ]);

        foreach (['modules', 'milestones', 'checklist_items'] as $field) {
            $data[$field] = collect(preg_split('/\r\n|\r|\n/', (string) ($data[$field] ?? '')))
                ->map(fn (string $line): string => trim($line))
                ->filter()
                ->values()
                ->all();
        }
        $data['created_by_user_id'] = $request->user()->id;
        ProjectTemplate::create($data);

        return back()->with('success', 'Project template created.');
    }

    public function applyTemplate(Request $request, Project $project, ProjectTemplate $template): RedirectResponse
    {
        DB::transaction(function () use ($project, $template): void {
            $project->update([
                'objectives' => $project->objectives ?: $template->objectives,
                'scope_in' => $project->scope_in ?: $template->scope_in,
                'scope_out' => $project->scope_out ?: $template->scope_out,
            ]);

            foreach ($template->modules ?? [] as $index => $name) {
                $module = $project->modules()->firstOrCreate(
                    ['name' => $name],
                    ['description' => null, 'sort_order' => $project->modules()->count() + $index],
                );
            }
            foreach ($template->milestones ?? [] as $index => $name) {
                $project->milestones()->firstOrCreate(
                    ['name' => $name],
                    ['description' => null, 'status' => 'planned', 'sort_order' => $project->milestones()->count() + $index],
                );
            }
            if (($template->checklist_items ?? []) !== []) {
                $checklist = $project->checklists()->create([
                    'title' => $template->name.' delivery checklist',
                    'sort_order' => $project->checklists()->count(),
                ]);
                foreach ($template->checklist_items as $index => $title) {
                    $checklist->items()->create([
                        'title' => $title,
                        'is_required' => true,
                        'sort_order' => $index,
                    ]);
                }
            }
        });

        return back()->with('success', 'Project template applied.');
    }

    public function storeApproval(Request $request, Project $project): RedirectResponse
    {
        $project->approvals()->create([
            ...$request->validate([
                'type' => ['required', Rule::in(['quotation', 'requirement', 'milestone', 'deliverable'])],
                'title' => ['required', 'string', 'max:180'],
                'description' => ['nullable', 'string', 'max:5000'],
            ]),
            'created_by_user_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Client approval request added.');
    }

    public function respondToApproval(Request $request, Project $project, ProjectApproval $approval): RedirectResponse
    {
        abort_unless($approval->project_id === $project->id, 404);
        $approval->update([
            ...$request->validate([
                'status' => ['required', Rule::in(['approved', 'changes_requested', 'rejected'])],
                'customer_response' => ['nullable', 'string', 'max:5000'],
            ]),
            'responded_by_user_id' => $request->user()->id,
            'responded_at' => now(),
        ]);

        return back()->with('success', 'Approval response saved.');
    }

    public function storeDocument(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'category' => ['required', Rule::in(['contract', 'drawing', 'handover', 'warranty', 'meeting', 'other'])],
            'document_date' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'visible_to_customer' => ['nullable', 'boolean'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,webp', 'max:20480'],
        ]);
        $file = $request->file('file');
        $path = $file->store('project-documents', 'local');

        $project->projectDocuments()->create([
            'uploaded_by' => $request->user()->id,
            'title' => $data['title'],
            'category' => $data['category'],
            'document_date' => $data['document_date'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'visible_to_customer' => $request->boolean('visible_to_customer'),
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return back()->with('success', 'Project document uploaded.');
    }

    public function downloadDocument(Request $request, Project $project, ProjectDocument $document): BinaryFileResponse
    {
        abort_unless($document->project_id === $project->id, 404);
        if ($request->user()->hasRole('customer')) {
            abort_unless($document->visible_to_customer, 404);
        }
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return response()->download(
            Storage::disk('local')->path($document->path),
            $document->original_name,
            ['Content-Type' => $document->mime_type],
        );
    }

    public function storeChecklist(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'items' => ['nullable', 'string', 'max:5000'],
        ]);
        $checklist = $project->checklists()->create([
            'title' => $data['title'],
            'sort_order' => $project->checklists()->count(),
        ]);
        foreach (preg_split('/\r\n|\r|\n/', (string) ($data['items'] ?? '')) as $index => $title) {
            $title = trim($title);
            if ($title !== '') {
                $checklist->items()->create([
                    'title' => $title,
                    'is_required' => false,
                    'sort_order' => $index,
                ]);
            }
        }

        return back()->with('success', 'Project checklist added.');
    }

    public function toggleChecklistItem(Request $request, Project $project, ProjectChecklistItem $item): RedirectResponse
    {
        abort_unless($item->checklist->project_id === $project->id, 404);
        $completed = ! $item->is_completed;
        $item->update([
            'is_completed' => $completed,
            'completed_by' => $completed ? $request->user()->id : null,
            'completed_at' => $completed ? now() : null,
        ]);

        return back();
    }

    public function storeRisk(Request $request, Project $project): RedirectResponse
    {
        $project->risks()->create($request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'probability' => ['required', Rule::in(['low', 'medium', 'high'])],
            'impact' => ['required', Rule::in(['low', 'medium', 'high'])],
            'mitigation' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['open', 'mitigating', 'resolved', 'accepted'])],
            'due_on' => ['nullable', 'date'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
        ]));

        return back()->with('success', 'Project risk added.');
    }

    public function updateRisk(Request $request, Project $project, ProjectRisk $risk): RedirectResponse
    {
        abort_unless($risk->project_id === $project->id, 404);
        $risk->update($request->validate([
            'status' => ['required', Rule::in(['open', 'mitigating', 'resolved', 'accepted'])],
            'mitigation' => ['nullable', 'string', 'max:5000'],
        ]));

        return back()->with('success', 'Project risk updated.');
    }

    public function updateRetention(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'retention_days' => ['nullable', 'integer', 'min:1', 'max:36500'],
            'action' => ['required', Rule::in(['archive', 'delete'])],
            'legal_hold' => ['nullable', 'boolean'],
        ]);
        $project->retentionPolicy()->updateOrCreate(
            ['project_id' => $project->id],
            [
                'retention_days' => $data['retention_days'] ?? null,
                'action' => $data['action'],
                'legal_hold' => $request->boolean('legal_hold'),
            ],
        );

        return back()->with('success', 'Retention policy saved.');
    }

    public function storeTimeEntry(Request $request, Project $project): RedirectResponse
    {
        $project->timeEntries()->create([
            ...$request->validate([
                'worked_on' => ['required', 'date'],
                'hours' => ['required', 'numeric', 'min:0.01', 'max:1000'],
                'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
                'notes' => ['nullable', 'string', 'max:5000'],
            ]),
            'user_id' => $request->user()->id,
            'hourly_rate' => $request->input('hourly_rate', 0),
            'billable' => $request->boolean('billable'),
        ]);

        return back()->with('success', 'Time entry added.');
    }

    public function storeCostEntry(Request $request, Project $project): RedirectResponse
    {
        $project->costEntries()->create($request->validate([
            'category' => ['required', Rule::in(['labor', 'hardware', 'software', 'travel', 'other'])],
            'title' => ['required', 'string', 'max:180'],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'incurred_on' => ['required', 'date'],
        ]));

        return back()->with('success', 'Project cost added.');
    }

    public function destroyCostEntry(Project $project, ProjectCostEntry $entry): RedirectResponse
    {
        abort_unless($entry->project_id === $project->id, 404);
        $entry->delete();

        return back()->with('success', 'Project cost removed.');
    }

    public function storeFeedback(Request $request, Project $project): RedirectResponse
    {
        abort_unless(
            Project::query()->visibleToCustomer($request->user())->whereKey($project->id)->exists(),
            404,
        );
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);
        $project->feedback()->updateOrCreate(
            ['customer_user_id' => $request->user()->id],
            [...$data, 'submitted_at' => now()],
        );

        return back()->with('success', 'Thank you for your feedback.');
    }
}
