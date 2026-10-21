<?php

namespace App\Http\Controllers;

use App\Models\CustomerMessageTemplate;
use App\Support\DataTableQueryBuilder;
use App\Support\StatusBadge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\QueryBuilder\AllowedFilter;
use Yajra\DataTables\Facades\DataTables;

class CustomerMessageTemplateController extends Controller
{
    public function index(): View
    {
        return view('customer-messages.index', [
            'stages' => CustomerMessageTemplate::STAGES,
        ]);
    }

    public function datatable(Request $request)
    {
        $query = CustomerMessageTemplate::query();
        $stage = $request->input('stage');
        if (is_string($stage) && isset(CustomerMessageTemplate::STAGES[$stage])) {
            $query->where('stage', $stage);
        }

        return DataTables::eloquent(DataTableQueryBuilder::for($query, $request, [
            'stage' => AllowedFilter::exact('stage'),
            'name' => AllowedFilter::partial('name'),
            'subject' => AllowedFilter::partial('subject'),
            'channel' => AllowedFilter::exact('channel'),
        ]))
            ->filter(function ($templates) use ($request): void {
                $search = trim((string) $request->input('search.value', ''));
                if ($search !== '') {
                    $templates->where(fn ($matches) => $matches
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%"));
                }
            }, true)
            ->addColumn('template', fn (CustomerMessageTemplate $template): string => sprintf(
                '<a class="fw-semibold" href="%s">%s</a>%s',
                e(route('customer-messages.show', $template)),
                e($template->name),
                $template->subject ? '<div class="small text-body-secondary">'.e($template->subject).'</div>' : '',
            ))
            ->addColumn('stage_label', fn (CustomerMessageTemplate $template): string => CustomerMessageTemplate::STAGES[$template->stage])
            ->addColumn('channel_label', fn (CustomerMessageTemplate $template): string => CustomerMessageTemplate::CHANNELS[$template->channel])
            ->addColumn('status_label', fn (CustomerMessageTemplate $template): string => (string) StatusBadge::make(
                $template->is_active ? 'active' : 'archived',
                $template->is_active ? 'Available' : 'Archived',
            ))
            ->addColumn('actions', fn (CustomerMessageTemplate $template): string => sprintf(
                '<a class="btn btn-sm btn-outline-secondary" href="%s">Preview</a>',
                e(route('customer-messages.show', $template)),
            ))
            ->rawColumns(['template', 'status_label', 'actions'])
            ->toJson();
    }

    public function create(): View
    {
        return view('customer-messages.create', [
            'template' => new CustomerMessageTemplate(['channel' => 'email', 'stage' => 'general', 'is_active' => true]),
            'stages' => CustomerMessageTemplate::STAGES,
            'channels' => CustomerMessageTemplate::CHANNELS,
            'placeholders' => CustomerMessageTemplate::PLACEHOLDERS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(6));
        $data['is_active'] = $request->boolean('is_active');
        $data['created_by_user_id'] = $request->user()->id;
        $template = CustomerMessageTemplate::create($data);

        return redirect()->route('customer-messages.show', $template)->with('success', 'Message template created.');
    }

    public function show(CustomerMessageTemplate $customerMessage): View
    {
        $customerMessage->load('attachments.uploader');

        return view('customer-messages.show', [
            'template' => $customerMessage,
            'canManage' => request()->user()->hasRole('admin')
                || (int) $customerMessage->created_by_user_id === (int) request()->user()->id,
            'eyebrow' => CustomerMessageTemplate::STAGES[$customerMessage->stage]
                .' · '.CustomerMessageTemplate::CHANNELS[$customerMessage->channel],
            'subjectPreview' => $customerMessage->render($customerMessage->subject ?? ''),
            'bodyPreview' => $customerMessage->render($customerMessage->body),
            'placeholders' => CustomerMessageTemplate::PLACEHOLDERS,
        ]);
    }

    public function edit(CustomerMessageTemplate $customerMessage): View
    {
        return view('customer-messages.edit', [
            'template' => $customerMessage,
            'stages' => CustomerMessageTemplate::STAGES,
            'channels' => CustomerMessageTemplate::CHANNELS,
            'placeholders' => CustomerMessageTemplate::PLACEHOLDERS,
        ]);
    }

    public function update(Request $request, CustomerMessageTemplate $customerMessage): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['is_active'] = $request->boolean('is_active');
        $customerMessage->update($data);

        return redirect()->route('customer-messages.show', $customerMessage)->with('success', 'Message template updated.');
    }

    public function destroy(CustomerMessageTemplate $customerMessage): RedirectResponse
    {
        $customerMessage->delete();

        return redirect()->route('customer-messages.index')->with('success', 'Message template deleted.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'stage' => ['required', Rule::in(array_keys(CustomerMessageTemplate::STAGES))],
            'channel' => ['required', Rule::in(array_keys(CustomerMessageTemplate::CHANNELS))],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
        ]);
    }
}
