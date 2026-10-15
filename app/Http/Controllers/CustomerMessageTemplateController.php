<?php

namespace App\Http\Controllers;

use App\Models\CustomerMessageTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerMessageTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $query = CustomerMessageTemplate::query();
        if ($request->filled('stage') && isset(CustomerMessageTemplate::STAGES[$request->input('stage')])) {
            $query->where('stage', $request->input('stage'));
        }
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn ($templates) => $templates
                ->where('name', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%")
                ->orWhere('body', 'like', "%{$search}%"));
        }

        return view('customer-messages.index', [
            'templates' => $query->orderBy('stage')->orderBy('name')->paginate(25)->withQueryString(),
            'stages' => CustomerMessageTemplate::STAGES,
        ]);
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
        $data['slug'] = Str::slug($data['name']) . '-' . Str::lower(Str::random(6));
        $data['is_active'] = $request->boolean('is_active');
        $template = CustomerMessageTemplate::create($data);

        return redirect()->route('customer-messages.show', $template)->with('success', 'Message template created.');
    }

    public function show(CustomerMessageTemplate $customerMessage): View
    {
        $customerMessage->load('attachments.uploader');

        return view('customer-messages.show', [
            'template' => $customerMessage,
            'eyebrow' => CustomerMessageTemplate::STAGES[$customerMessage->stage]
                . ' · ' . CustomerMessageTemplate::CHANNELS[$customerMessage->channel],
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