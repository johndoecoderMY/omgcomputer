<?php

namespace App\Http\Controllers;

use App\Mail\BillingReceiptMail;
use App\Mail\InvoiceMail;
use App\Mail\QuotationMail;
use App\Models\BillingDocument;
use App\Models\BillingPayment;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\CustomerMessageTemplate;
use App\Models\CustomPcComponent;
use App\Models\InventoryPart;
use App\Models\ProductService;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\User;
use App\Notifications\QuotationInAppNotification;
use App\Services\BillingReceiptService;
use App\Services\ChipCollect;
use App\Services\GuestInvoicePaymentLinkService;
use App\Services\InventoryStock;
use App\Services\MailSettingsService;
use App\Services\WorkspaceNotifier;
use App\Support\DataTableQueryBuilder;
use App\Support\RepairTicketIssueSanitizer;
use App\Support\StatusBadge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Mpdf\Mpdf;
use Spatie\QueryBuilder\AllowedFilter;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class BillingController extends Controller
{
    public function index(Request $request): View
    {
        return view($request->user()->hasRole('customer') ? 'billing.customer-index' : 'billing.index');
    }

    public function receipts(Request $request): View
    {
        $payments = BillingPayment::query()
            ->with(['document', 'user'])
            ->whereHas('document', function (Builder $documents) use ($request): void {
                $documents->where('type', 'invoice');
                if ($request->user()->hasRole('customer')) {
                    $this->applyCustomerDocumentVisibility($documents, $request->user());
                }
            })
            ->latest('paid_at')
            ->paginate(25);
        $guestReceiptUrls = [];
        if (! $request->user()->hasRole('customer')) {
            $guestReceiptUrls = $payments->getCollection()
                ->mapWithKeys(fn (BillingPayment $payment): array => [
                    $payment->id => $this->guestPdfUrls('billing.guest.receipts.pdf', ['payment' => $payment]),
                ])
                ->all();
        }

        return view('billing.receipts', compact('payments', 'guestReceiptUrls'));
    }

    public function datatable(Request $request)
    {
        $query = $request->user()->hasRole('customer')
            ? $this->customerDocumentsQuery($request->user())
            : BillingDocument::query();
        if (in_array($request->input('type'), ['invoice', 'quotation'], true)) {
            $query->where('type', $request->input('type'));
        }
        if (in_array($request->input('status'), ['draft', 'sent', 'accepted', 'rejected', 'issued', 'partial', 'part_paid', 'paid', 'void'], true)) {
            $query->where('status', $request->input('status'));
        }

        return DataTables::eloquent(DataTableQueryBuilder::for($query, $request, [
            'type' => AllowedFilter::exact('type'),
            'status' => AllowedFilter::exact('status'),
            'number' => AllowedFilter::partial('number'),
            'customer_name' => AllowedFilter::partial('customer_name'),
            'customer_email' => AllowedFilter::partial('customer_email'),
        ]))
            ->filter(function ($documents) use ($request): void {
                $search = trim((string) $request->input('search.value', ''));
                if ($search !== '') {
                    $documents->where(fn ($matches) => $matches
                        ->where('number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%"));
                }
            }, true)
            ->addColumn('document', fn (BillingDocument $document): string => sprintf(
                '<a class="fw-semibold" href="%s">%s</a><div class="small text-body-secondary">%s</div>',
                e(route('billing.documents.show', $document)),
                e($document->number),
                e(ucfirst($document->type)),
            ))
            ->editColumn('issue_date', fn (BillingDocument $document): string => $document->issue_date->format('M j, Y'))
            ->addColumn('status_label', fn (BillingDocument $document): string => (string) StatusBadge::make(
                $document->status,
                str_replace('_', ' ', ucfirst($document->status)),
            ))
            ->editColumn('total', fn (BillingDocument $document): string => 'RM '.number_format((float) $document->total, 2))
            ->editColumn('paid_amount', fn (BillingDocument $document): string => $document->type === 'invoice'
                ? 'RM '.number_format((float) $document->paid_amount, 2)
                : '—')
            ->addColumn('balance', fn (BillingDocument $document): string => $document->type === 'invoice'
                ? 'RM '.number_format($document->balance, 2)
                : '—')
            ->rawColumns(['document', 'status_label'])
            ->toJson();
    }

    public function create(Request $request): View|RedirectResponse
    {
        if ($request->input('type') === 'invoice') {
            return redirect()
                ->route('billing.create', array_merge($request->only(['project_id', 'repair_ticket_id']), ['type' => 'quotation']))
                ->with('info', 'Send a quotation and wait for customer acceptance before creating an invoice.');
        }

        $type = 'quotation';
        $ticket = $request->filled('repair_ticket_id')
            ? RepairTicket::query()->with('customer')->findOrFail($request->integer('repair_ticket_id'))
            : null;
        $project = $request->filled('project_id')
            ? Project::query()->with(['customers', 'repairTickets.customer'])->findOrFail($request->integer('project_id'))
            : $ticket?->project;
        $customer = $ticket?->customer ?? $project?->customers?->first() ?? $project?->repairTickets?->first()?->customer;
        $project?->loadMissing(['customers', 'customerAccount', 'repairTickets.customer']);
        $projectRecipients = $project ? $this->projectRecipients($project) : collect();
        $selectedRecipientKey = $projectRecipients
            ->first(fn (array $recipient): bool => strtolower(trim($recipient['email'])) === strtolower(trim((string) $customer?->email)))['key']
            ?? $projectRecipients->first()['key']
            ?? '';
        $ticketIssueSummary = $ticket
            ? app(RepairTicketIssueSanitizer::class)->plainText($ticket->issue_description)
            : null;
        $lines = [[
            'inventory_part_id' => null,
            'custom_pc_component_id' => null,
            'product_id' => null,
            'description' => $ticket ? 'Repair service: '.$ticketIssueSummary : ($project ? 'Project: '.$project->name : ''),
            'quantity' => 1,
            'unit_price' => null,
            'unit_cost' => null,
        ]];
        $customerRecipients = $this->customerRecipients();

        return view('billing.create', [
            'parts' => $this->selectedInventoryParts(collect($request->old('items', $lines))->pluck('inventory_part_id')),
            'products' => ProductService::query()->orderBy('name')->get(),
            'ticket' => $ticket,
            'project' => $project,
            'projectRecipients' => $projectRecipients,
            'selectedRecipientKey' => $selectedRecipientKey,
            'customerRecipients' => $customerRecipients,
            'selectedCustomerRecipientKey' => $this->selectedCustomerRecipientKey($customer, null, $customerRecipients),
            'document' => new BillingDocument([
                'type' => $type,
                'customer_name' => $customer?->name,
                'customer_email' => $customer?->email,
                'customer_phone' => $customer?->phone,
                'customer_address' => $customer?->address,
                'issue_date' => now('Asia/Kuala_Lumpur')->toDateString(),
                'tax_rate' => config('billing.tax_rate', 6),
                'included_revision_rounds' => 3,
                'notes' => $ticket ? "Repair ticket {$ticket->ticket_number}: {$ticketIssueSummary}" : null,
            ]),
            'lines' => $lines,
            'type' => $type,
            'formAction' => route('billing.store'),
            'editing' => false,
            'ticketModal' => $request->boolean('ticket_modal') && $ticket !== null,
        ]);
    }

    public function edit(BillingDocument $document): View
    {
        abort_unless(
            $document->type === 'quotation'
                && ($document->status === 'draft' || $document->status === 'rejected'),
            404,
        );
        $document->load('items');
        $document->loadMissing(['project.customers', 'project.customerAccount', 'project.repairTickets.customer']);
        $projectRecipients = $document->project
            ? $this->projectRecipients($document->project)
            : collect();
        $selectedRecipientKey = $projectRecipients
            ->first(fn (array $recipient): bool => strtolower(trim($recipient['email'])) === strtolower(trim((string) $document->customer_email)))['key']
            ?? '';
        $customerRecipients = $this->customerRecipients();

        return view('billing.create', [
            'parts' => $this->selectedInventoryParts($document->items->pluck('inventory_part_id')),
            'products' => ProductService::query()->orderBy('name')->get(),
            'ticket' => $document->repairTicket,
            'project' => $document->project,
            'projectRecipients' => $projectRecipients,
            'selectedRecipientKey' => $selectedRecipientKey,
            'customerRecipients' => $customerRecipients,
            'selectedCustomerRecipientKey' => $this->selectedCustomerRecipientKey(null, $document->customer_email, $customerRecipients),
            'document' => $document,
            'lines' => $document->items->map(fn ($item) => [
                'inventory_part_id' => $item->inventory_part_id,
                'custom_pc_component_id' => $item->custom_pc_component_id,
                'product_id' => null,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'unit_cost' => $item->unit_cost,
                'discount_amount' => $item->discount_amount,
            ])->all(),
            'type' => $document->type,
            'formAction' => route('billing.documents.update', $document),
            'editing' => true,
        ]);
    }

    public function searchInventoryParts(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);
        $likeTerm = '%'.trim($data['q']).'%';

        $parts = InventoryPart::query()
            ->select(['id', 'name', 'sku', 'selling_price', 'stock_quantity'])
            ->where(function (Builder $query) use ($likeTerm): void {
                $query->where('name', 'like', $likeTerm)
                    ->orWhere('sku', 'like', $likeTerm);
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (InventoryPart $part): array => [
                'id' => 'part:'.$part->id,
                'text' => $part->name.($part->sku ? ' · '.$part->sku : '').' ('.$part->stock_quantity.' available)',
                'name' => $part->name,
                'price' => $part->selling_price,
            ]);

        $services = ProductService::query()
            ->select(['id', 'name', 'billing_price'])
            ->where('is_active', true)
            ->where('name', 'like', $likeTerm)
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (ProductService $service): array => [
                'id' => 'service:'.$service->id,
                'text' => $service->name.((float) $service->billing_price > 0
                    ? ' · RM'.number_format((float) $service->billing_price, 2)
                    : ' · Custom price'),
                'name' => $service->name,
                'price' => $service->billing_price,
            ]);

        $customLine = str_contains(strtolower($data['q']), 'custom')
            || str_contains(strtolower($data['q']), 'manual')
            ? collect([[
                'id' => 'custom:manual',
                'text' => 'Custom line · enter description and price manually',
                'name' => '',
                'price' => '',
            ]])
            : collect();

        $results = $parts->concat($services)->concat($customLine)
            ->sortBy(function (array $result) use ($data): array {
                $name = strtolower(trim((string) ($result['name'] ?? '')));
                $term = strtolower(trim($data['q']));

                return [
                    $name === $term ? 0 : ($name !== '' && str_starts_with($name, $term) ? 1 : 2),
                    strtolower((string) $result['text']),
                ];
            })
            ->take(20)
            ->values();

        return response()->json(['results' => $results]);
    }

    private function selectedInventoryParts(Collection $partIds): Collection
    {
        return InventoryPart::query()
            ->select(['id', 'name', 'selling_price', 'stock_quantity'])
            ->whereIn('id', $partIds->filter()->unique()->take(50))
            ->get()
            ->keyBy('id');
    }

    private function customerRecipients(): array
    {
        $records = Customer::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Customer $customer): array => [
                'key' => 'customer-record:'.$customer->id,
                'group' => 'Customer records',
                'name' => (string) $customer->name,
                'email' => (string) $customer->email,
                'phone' => (string) $customer->phone,
                'address' => (string) $customer->address,
            ]);

        $accounts = User::role('customer')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'address'])
            ->map(fn (User $account): array => [
                'key' => 'customer-account:'.$account->id,
                'group' => 'Customer accounts',
                'name' => (string) $account->name,
                'email' => (string) $account->email,
                'phone' => (string) $account->phone,
                'address' => (string) $account->address,
            ]);

        return $records->concat($accounts)->values()->all();
    }

    private function selectedCustomerRecipientKey(?Customer $customer, ?string $email = null, ?array $recipients = null): string
    {
        $recipientEmail = strtolower(trim($customer?->email ?? $email ?? ''));
        if ($recipientEmail === '') {
            return '';
        }

        foreach ($recipients ?? $this->customerRecipients() as $recipient) {
            if (strtolower(trim((string) $recipient['email'])) === strtolower(trim($recipientEmail))) {
                return $recipient['key'];
            }
        }

        return '';
    }

    private function projectRecipients(Project $project)
    {
        $customerRecipient = fn (Customer $customer): array => [
            'key' => 'customer:'.$customer->id,
            'name' => (string) $customer->name,
            'email' => (string) $customer->email,
            'phone' => (string) $customer->phone,
            'address' => (string) $customer->address,
        ];

        $recipients = $project->customers
            ->map($customerRecipient)
            ->concat($project->repairTickets
                ->pluck('customer')
                ->filter()
                ->unique('id')
                ->map($customerRecipient));

        if ($project->customerAccount) {
            $account = $project->customerAccount;
            $recipients->push([
                'key' => 'account:'.$account->id,
                'name' => (string) $account->name,
                'email' => (string) $account->email,
                'phone' => (string) $account->phone,
                'address' => (string) $account->address,
            ]);
        }

        return $recipients
            ->unique(fn (array $recipient): string => strtolower(trim($recipient['email'])) ?: $recipient['key'])
            ->values();
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->input('type') === 'invoice') {
            throw ValidationException::withMessages([
                'type' => 'Create and send a quotation first. An invoice can be issued only after the customer accepts it.',
            ]);
        }

        $data = $request->validate([
            'type' => ['required', 'in:quotation,quote'],
            'repair_ticket_id' => ['nullable', 'integer', 'exists:repair_tickets,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'modal_return_to_ticket' => ['nullable', 'integer', 'exists:repair_tickets,id'],
            'modal_return_to_project' => ['sometimes', 'boolean'],
            'customer_phone' => ['nullable', 'string', 'max:64'],
            'customer_address' => ['nullable', 'string', 'max:2000'],
            'issue_date' => ['nullable', 'date'],
            'issued_at' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'project_scope' => ['nullable', 'string', 'max:5000'],
            'included_revision_rounds' => ['nullable', 'integer', 'min:0', 'max:3'],
            'project_due_date' => ['nullable', 'date'],
            'payment_terms' => ['nullable', 'string', 'max:5000'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'custom_pc_build' => ['sometimes', 'boolean'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.inventory_part_id' => ['nullable', 'integer', 'exists:inventory_parts,id'],
            'items.*.custom_pc_component_id' => ['nullable', 'integer', 'exists:custom_pc_components,id'],
            'items.*.product_id' => ['nullable', 'string', Rule::exists('product_services', 'id')],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'items.*.build_group' => ['nullable', 'string', 'max:20'],
        ]);

        foreach ($data['items'] as $index => $item) {
            if (! empty($item['inventory_part_id']) && ! empty($item['product_id'])) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'Choose either an inventory item or a service, not both.',
                ]);
            }
            if (! empty($item['custom_pc_component_id']) && (! empty($item['inventory_part_id']) || ! empty($item['product_id']))) {
                throw ValidationException::withMessages([
                    "items.{$index}.custom_pc_component_id" => 'Choose either a builder component, an inventory item, or a service.',
                ]);
            }
            if (empty($item['inventory_part_id']) && empty($item['custom_pc_component_id']) && empty($item['product_id']) && empty($item['description'])) {
                throw ValidationException::withMessages([
                    "items.{$index}.description" => 'Enter a description or select an inventory item.',
                ]);
            }
            if (empty($item['inventory_part_id']) && empty($item['custom_pc_component_id']) && empty($item['product_id']) && ! isset($item['unit_price'])) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_price" => 'Enter a unit price for this line.',
                ]);
            }
        }

        $inventoryPartIds = collect($data['items'])
            ->pluck('inventory_part_id')
            ->filter()
            ->unique()
            ->values();
        $parts = InventoryPart::query()->whereIn('id', $inventoryPartIds)->get()->keyBy('id');
        $components = CustomPcComponent::query()
            ->whereIn('id', collect($data['items'])->pluck('custom_pc_component_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        if (! empty($data['custom_pc_build'])) {
            $allowedComponentTypes = [
                'cpu',
                'gpu',
                'ram',
                'motherboard',
                'storage',
                'power_supply',
                'case',
                'cooling',
                'operating_system',
                'software',
                'peripheral',
                'other',
            ];

            foreach ($data['items'] as $index => $item) {
                if (empty($item['inventory_part_id']) && empty($item['custom_pc_component_id'])) {
                    throw ValidationException::withMessages([
                        "items.{$index}.custom_pc_component_id" => 'Choose a PC component for every build line.',
                    ]);
                }

                $part = $components->get($item['custom_pc_component_id'] ?? null)
                    ?? $parts->get($item['inventory_part_id'] ?? null);
                $catalogPart = $part instanceof CustomPcComponent || $part?->catalog_currency === 'USD';
                if (! $part || (! $catalogPart && $part->stock_quantity < 1) || ! in_array($part->type, $allowedComponentTypes, true)) {
                    throw ValidationException::withMessages([
                        "items.{$index}.custom_pc_component_id" => 'Choose a PC component for every build line.',
                    ]);
                }
                if ($catalogPart && (float) $part->selling_price <= 0 && ! isset($item['unit_price'])) {
                    throw ValidationException::withMessages([
                        "items.{$index}.unit_price" => 'Enter an RM quote price for this catalog component.',
                    ]);
                }
                if (! isset($item['unit_cost'])) {
                    throw ValidationException::withMessages([
                        "items.{$index}.unit_cost" => 'Enter our unit cost in RM for each custom PC component.',
                    ]);
                }
            }
        }

        $compatibilityErrors = [];
        $buildGroups = collect($data['items'])->groupBy(fn (array $item): string => $item['build_group'] ?? '1');
        foreach ($buildGroups as $groupItems) {
            $cpus = [];
            $motherboards = [];
            foreach ($groupItems as $index => $item) {
                $part = $components->get($item['custom_pc_component_id'] ?? null)
                    ?? $parts->get($item['inventory_part_id'] ?? null);
                if ($part?->type === 'cpu') {
                    $cpus[] = [$index, $part];
                } elseif ($part?->type === 'motherboard') {
                    $motherboards[] = [$index, $part];
                }
            }

            foreach ($cpus as [$cpuIndex, $cpu]) {
                foreach ($motherboards as [$motherboardIndex, $motherboard]) {
                    $cpuIdField = empty($data['items'][$cpuIndex]['custom_pc_component_id'])
                        ? 'inventory_part_id'
                        : 'custom_pc_component_id';
                    $motherboardIdField = empty($data['items'][$motherboardIndex]['custom_pc_component_id'])
                        ? 'inventory_part_id'
                        : 'custom_pc_component_id';
                    $cpuSocket = $cpu->specification('Socket');
                    $motherboardSocket = $motherboard->specification('Socket');
                    if (! $cpuSocket || ! $motherboardSocket) {
                        $compatibilityErrors["items.{$cpuIndex}.{$cpuIdField}"] = 'Add socket specifications to both the selected processor and motherboard before quoting this build.';

                        continue;
                    }

                    if (strcasecmp(trim($cpuSocket), trim($motherboardSocket)) !== 0) {
                        $compatibilityErrors["items.{$cpuIndex}.{$cpuIdField}"] = "The selected {$cpuSocket} processor is not compatible with the {$motherboardSocket} motherboard.";
                        $compatibilityErrors["items.{$motherboardIndex}.{$motherboardIdField}"] = 'Choose a motherboard with a socket compatible with the selected processor.';
                    }
                }
            }
        }

        if ($compatibilityErrors !== []) {
            throw ValidationException::withMessages($compatibilityErrors);
        }

        $type = $data['type'] === 'quote' ? 'quotation' : $data['type'];
        $issueDate = $data['issue_date'] ?? $data['issued_at'] ?? now('Asia/Kuala_Lumpur')->toDateString();
        $isProjectQuotation = $type === 'quotation' && ! empty($data['project_id']);
        if (! empty($data['due_date']) && $data['due_date'] < $issueDate) {
            throw ValidationException::withMessages(['due_date' => 'The due date must be on or after the issue date.']);
        }
        if ($isProjectQuotation && ! empty($data['project_due_date']) && $data['project_due_date'] < $issueDate) {
            throw ValidationException::withMessages(['project_due_date' => 'The project target date must be on or after the quotation issue date.']);
        }

        $document = DB::transaction(function () use ($data, $request, $type, $issueDate, $isProjectQuotation): BillingDocument {
            $document = BillingDocument::create([
                'number' => 'TEMP-'.Str::upper(Str::random(12)),
                'type' => $type,
                'status' => 'draft',
                'user_id' => $request->user()->id,
                'repair_ticket_id' => $data['repair_ticket_id'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_address' => $data['customer_address'] ?? null,
                'issue_date' => $issueDate,
                'due_date' => $data['due_date'] ?? null,
                'project_scope' => $isProjectQuotation ? ($data['project_scope'] ?? null) : null,
                'included_revision_rounds' => $isProjectQuotation ? ($data['included_revision_rounds'] ?? 3) : 3,
                'project_due_date' => $isProjectQuotation ? ($data['project_due_date'] ?? null) : null,
                'payment_terms' => $isProjectQuotation ? ($data['payment_terms'] ?? null) : null,
                'tax_rate' => $data['tax_rate'],
                'notes' => $data['notes'] ?? null,
            ]);

            $subtotal = 0;
            $itemDiscountTotal = 0;
            foreach ($data['items'] as $index => $item) {
                $part = ! empty($item['inventory_part_id'])
                    ? InventoryPart::query()->findOrFail($item['inventory_part_id'])
                    : null;
                $component = ! empty($item['custom_pc_component_id'])
                    ? CustomPcComponent::query()->findOrFail($item['custom_pc_component_id'])
                    : null;
                $product = ! empty($item['product_id'])
                    ? ProductService::withTrashed()->findOrFail($item['product_id'])
                    : null;
                $price = round((float) ($item['unit_price'] ?? $component?->selling_price ?? $part?->selling_price ?? $product['billing_price'] ?? 0), 2);
                $lineTotal = round($price * (int) $item['quantity'], 2);
                $lineDiscount = round((float) ($item['discount_amount'] ?? 0), 2);
                if ($lineDiscount > $lineTotal) {
                    throw ValidationException::withMessages([
                        "items.{$index}.discount_amount" => 'The line discount cannot be greater than the line total.',
                    ]);
                }
                $subtotal += $lineTotal;
                $itemDiscountTotal += $lineDiscount;

                $document->items()->create([
                    'inventory_part_id' => $part?->id,
                    'custom_pc_component_id' => $component?->id,
                    'description' => $component?->name ?? $part?->name ?? $product['name'] ?? $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $price,
                    'unit_cost' => $item['unit_cost'] ?? null,
                    'line_total' => $lineTotal,
                    'discount_amount' => $lineDiscount,
                ]);
            }

            $discountAmount = round((float) ($data['discount_amount'] ?? 0), 2);
            $subtotalAfterItemDiscounts = round($subtotal - $itemDiscountTotal, 2);
            if ($discountAmount > $subtotalAfterItemDiscounts) {
                throw ValidationException::withMessages([
                    'discount_amount' => 'The overall discount cannot be greater than the subtotal after line discounts.',
                ]);
            }
            $taxableSubtotal = round($subtotalAfterItemDiscounts - $discountAmount, 2);
            $taxAmount = round($taxableSubtotal * (float) $data['tax_rate'] / 100, 2);
            $document->update([
                'number' => ($type === 'quotation' ? 'QUO-' : 'INV-')
                    .now()->format('Y').'-'.str_pad((string) $document->id, 6, '0', STR_PAD_LEFT),
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total' => round($taxableSubtotal + $taxAmount, 2),
            ]);

            return $document;
        });

        if ((int) ($data['modal_return_to_ticket'] ?? 0) === (int) $document->repair_ticket_id
            && $document->repair_ticket_id) {
            return redirect()->route('tickets.show', $document->repair_ticket_id)
                ->with('success', 'Quotation saved as a draft.');
        }

        if (! empty($data['modal_return_to_project']) && $document->project_id) {
            return redirect()->route('projects.show', $document->project_id)
                ->with('success', 'Quotation saved as a draft.');
        }

        return redirect()->route('billing.documents.show', $document)->with('success', 'Quotation saved as a draft.');
    }

    public function update(Request $request, BillingDocument $document): RedirectResponse
    {
        $canEdit = fn (BillingDocument $billingDocument): bool => $billingDocument->status === 'draft'
            || ($billingDocument->type === 'quotation' && $billingDocument->status === 'rejected');
        abort_unless($canEdit($document), 404);
        $data = $request->validate([
            'type' => ['required', Rule::in([$document->type])],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:64'],
            'customer_address' => ['nullable', 'string', 'max:2000'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'project_scope' => ['nullable', 'string', 'max:5000'],
            'included_revision_rounds' => ['nullable', 'integer', 'min:0', 'max:3'],
            'project_due_date' => ['nullable', 'date'],
            'payment_terms' => ['nullable', 'string', 'max:5000'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.inventory_part_id' => ['nullable', 'integer', 'exists:inventory_parts,id'],
            'items.*.custom_pc_component_id' => ['nullable', 'integer', 'exists:custom_pc_components,id'],
            'items.*.product_id' => ['nullable', 'string', Rule::exists('product_services', 'id')],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
        ]);

        $this->validateLineItems($data['items']);
        if (! empty($data['due_date']) && $data['due_date'] < $data['issue_date']) {
            throw ValidationException::withMessages(['due_date' => 'The due date must be on or after the issue date.']);
        }
        if ($document->type === 'quotation' && $document->project_id && ! empty($data['project_due_date']) && $data['project_due_date'] < $data['issue_date']) {
            throw ValidationException::withMessages(['project_due_date' => 'The project target date must be on or after the quotation issue date.']);
        }

        DB::transaction(function () use ($document, $data, $canEdit): void {
            $locked = BillingDocument::query()->lockForUpdate()->findOrFail($document->id);
            abort_unless($canEdit($locked), 404);
            $wasRejectedQuotation = $locked->type === 'quotation' && $locked->status === 'rejected';
            $locked->update([
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_address' => $data['customer_address'] ?? null,
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
                'project_scope' => $locked->type === 'quotation' && $locked->project_id ? ($data['project_scope'] ?? null) : null,
                'included_revision_rounds' => $locked->type === 'quotation' && $locked->project_id ? ($data['included_revision_rounds'] ?? 3) : 3,
                'project_due_date' => $locked->type === 'quotation' && $locked->project_id ? ($data['project_due_date'] ?? null) : null,
                'payment_terms' => $locked->type === 'quotation' && $locked->project_id ? ($data['payment_terms'] ?? null) : null,
                'tax_rate' => $data['tax_rate'],
                'notes' => $data['notes'] ?? null,
                ...($wasRejectedQuotation ? ['status' => 'draft'] : []),
            ]);
            $existingUnitCosts = $locked->items()->pluck('unit_cost')->all();
            $locked->items()->delete();

            $subtotal = 0;
            $itemDiscountTotal = 0;
            foreach ($data['items'] as $index => $item) {
                $part = ! empty($item['inventory_part_id'])
                    ? InventoryPart::query()->findOrFail($item['inventory_part_id'])
                    : null;
                $component = ! empty($item['custom_pc_component_id'])
                    ? CustomPcComponent::query()->findOrFail($item['custom_pc_component_id'])
                    : null;
                $product = ! empty($item['product_id'])
                    ? ProductService::withTrashed()->findOrFail($item['product_id'])
                    : null;
                $price = round((float) ($item['unit_price'] ?? $component?->selling_price ?? $part?->selling_price ?? $product['billing_price'] ?? 0), 2);
                $lineTotal = round($price * (int) $item['quantity'], 2);
                $lineDiscount = round((float) ($item['discount_amount'] ?? 0), 2);
                if ($lineDiscount > $lineTotal) {
                    throw ValidationException::withMessages([
                        "items.{$index}.discount_amount" => 'The line discount cannot be greater than the line total.',
                    ]);
                }
                $subtotal += $lineTotal;
                $itemDiscountTotal += $lineDiscount;
                $locked->items()->create([
                    'inventory_part_id' => $part?->id,
                    'custom_pc_component_id' => $component?->id,
                    'description' => $component?->name ?? $part?->name ?? $product['name'] ?? $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $price,
                    'unit_cost' => $item['unit_cost'] ?? $existingUnitCosts[$index] ?? null,
                    'line_total' => $lineTotal,
                    'discount_amount' => $lineDiscount,
                ]);
            }

            $discountAmount = round((float) ($data['discount_amount'] ?? 0), 2);
            $subtotalAfterItemDiscounts = round($subtotal - $itemDiscountTotal, 2);
            if ($discountAmount > $subtotalAfterItemDiscounts) {
                throw ValidationException::withMessages([
                    'discount_amount' => 'The overall discount cannot be greater than the subtotal after line discounts.',
                ]);
            }
            $taxableSubtotal = round($subtotalAfterItemDiscounts - $discountAmount, 2);
            $taxAmount = round($taxableSubtotal * (float) $data['tax_rate'] / 100, 2);
            $locked->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total' => round($taxableSubtotal + $taxAmount, 2),
            ]);
        });

        return redirect()->route('billing.documents.show', $document)->with(
            'success',
            $document->type === 'quotation' && $document->status === 'rejected'
                ? 'Rejected quotation updated and returned to draft. Mark it as sent when ready.'
                : 'Draft billing document updated.',
        );
    }

    public function show(
        BillingDocument $document,
        ChipCollect $chip,
        GuestInvoicePaymentLinkService $paymentLinks,
        Request $request,
    ): View
    {
        $this->authorizeCustomerDocument($request, $document);
        $document->load([
            'items.part',
            'payments' => fn ($query) => $query->latest('paid_at'),
            'project.customers',
            'repairTicket.customer',
            'repairTicket.project.customers',
        ]);
        $guestDocumentUrls = [];
        $guestReceiptUrls = [];
        $whatsappActionUrls = [];
        if (! $request->user()->hasRole('customer')) {
            if (in_array($document->type, ['quotation', 'invoice'], true)) {
                $guestDocumentUrls = $this->guestPdfUrls(
                    'billing.guest.documents.pdf',
                    ['document' => $document],
                );
            }
            $guestReceiptUrls = $document->payments
                ->mapWithKeys(fn (BillingPayment $payment): array => [
                    $payment->id => $this->guestPdfUrls('billing.guest.receipts.pdf', ['payment' => $payment]),
                ])
                ->all();
            if ($document->type === 'quotation') {
                foreach (CustomerMessageTemplate::LANGUAGES as $locale => $label) {
                    $whatsappActionUrls[$locale] = [
                        'accept_url' => URL::signedRoute('billing.quotations.respond', [
                            'document' => $document,
                            'status' => 'accepted',
                            'locale' => $locale,
                        ]),
                        'reject_url' => URL::signedRoute('billing.quotations.respond', [
                            'document' => $document,
                            'status' => 'rejected',
                            'locale' => $locale,
                        ]),
                    ];
                }
            }
        }
        $canSharePaymentLink = ! $request->user()->hasRole('customer')
            && $chip->isConfigured()
            && $paymentLinks->isPayableInvoice($document)
            && (bool) filter_var($document->customer_email, FILTER_VALIDATE_EMAIL);
        $customerRecord = $document->repairTicket?->customer;
        $project = $document->project ?? $document->repairTicket?->project;
        $projectCustomers = $project?->customers ?? collect();
        if (! $customerRecord && $projectCustomers->isNotEmpty()) {
            $email = strtolower(trim((string) $document->customer_email));
            $customerRecord = $email !== ''
                ? $projectCustomers->first(fn (Customer $customer): bool => strtolower(trim((string) $customer->email)) === $email)
                : null;
            $customerRecord ??= $projectCustomers->count() === 1 ? $projectCustomers->first() : null;
        }

        return view('billing.show', [
            'document' => $document,
            'customerRecord' => $customerRecord,
            'chipEnabled' => $chip->isConfigured(),
            'guestDocumentUrls' => $guestDocumentUrls,
            'guestReceiptUrls' => $guestReceiptUrls,
            'whatsappActionUrls' => $whatsappActionUrls,
            'canSharePaymentLink' => $canSharePaymentLink,
        ]);
    }

    public function guestDocumentPdf(Request $request, BillingDocument $document): Response
    {
        abort_unless(
            ($document->type === 'quotation' && in_array($document->status, ['sent', 'accepted', 'rejected'], true))
                || ($document->type === 'invoice' && in_array($document->status, ['issued', 'partial', 'part_paid', 'paid'], true)),
            404,
        );
        $document->load(['items', 'payments']);

        return response($this->renderPdf($document, $this->billingLocale($request)))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.strtolower($document->number).'.pdf"')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('Cache-Control', 'no-store, private');
    }

    public function guestReceiptPdf(
        Request $request,
        BillingPayment $payment,
        BillingReceiptService $receipts,
    ): Response {
        $payment->load('document.items');
        abort_unless($payment->document->type === 'invoice', 404);

        return response($receipts->renderPdf($payment, $this->billingLocale($request)))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.strtolower($payment->receipt_number).'.pdf"')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('Cache-Control', 'no-store, private');
    }

    private function authorizeCustomerDocument(Request $request, BillingDocument $document): void
    {
        if ($request->user()->hasRole('admin') || ! $request->user()->hasRole('customer')) {
            return;
        }

        abort_unless(
            $this->customerDocumentsQuery($request->user())
                ->whereKey($document->getKey())
                ->exists(),
            404,
        );
    }

    private function customerDocumentsQuery(User $user): Builder
    {
        return $this->applyCustomerDocumentVisibility(BillingDocument::query(), $user);
    }

    private function applyCustomerDocumentVisibility(Builder $query, User $user): Builder
    {
        $email = strtolower(trim($user->email));
        if ($email === '') {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->whereIn('type', ['invoice', 'quotation'])
            ->where('status', '!=', 'draft')
            ->whereRaw('LOWER(TRIM(customer_email)) = ?', [$email])
            ->where(function (Builder $documents) use ($user, $email): void {
                $documents->whereHas('project', fn (Builder $projects) => $projects->visibleToCustomer($user))
                    ->orWhereHas('repairTicket.project', fn (Builder $projects) => $projects->visibleToCustomer($user))
                    ->orWhereHas('repairTicket.customer', fn (Builder $customers) => $customers
                        ->whereRaw('LOWER(TRIM(email)) = ?', [$email]))
                    ->orWhere(function (Builder $directDocument): void {
                        $directDocument->whereNull('project_id')->whereNull('repair_ticket_id');
                    });
            });
    }

    public function issue(
        BillingDocument $document,
        Request $request,
        InventoryStock $stock,
        ChipCollect $chip,
        GuestInvoicePaymentLinkService $paymentLinks,
    ): RedirectResponse
    {
        $invoice = $this->issueAcceptedQuotation($document, $request->user(), $stock);
        $emailSent = $this->sendBillingDocumentEmail(
            $invoice,
            $invoice->customer_email,
            $this->billingLocale($request),
            $chip,
            $paymentLinks,
        );

        return $this->invoiceEmailRedirect($invoice, $emailSent);
    }

    public function updateQuotationStatus(
        BillingDocument $document,
        Request $request,
        InventoryStock $stock,
        ChipCollect $chip,
        GuestInvoicePaymentLinkService $paymentLinks,
    ): RedirectResponse {
        $user = $request->user();
        if ($user->hasRole('customer')) {
            abort_unless(in_array($dataStatus = $request->input('status'), ['accepted', 'rejected'], true), 403);
            abort_unless($document->status === 'sent' && $document->type === 'quotation', 422);
            abort_unless($this->customerDocumentsQuery($user)->whereKey($document->id)->exists(), 404);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(['sent', 'accepted', 'rejected'])],
            'delivery' => ['nullable', Rule::in(['in_app', 'whatsapp'])],
        ]);

        $invoice = DB::transaction(function () use ($document, $data, $user, $stock): ?BillingDocument {
            $locked = BillingDocument::query()->lockForUpdate()->findOrFail($document->id);
            abort_unless($locked->type === 'quotation', 404);
            abort_if($locked->status === 'void', 422, 'Voided quotations cannot be changed.');
            if ($user->hasRole('customer')) {
                abort_unless($locked->status === 'sent', 422, 'Only sent quotations can be accepted or rejected by the customer.');
            } elseif ($locked->status === 'draft' && $data['status'] === 'rejected') {
                abort(422, 'A draft quotation cannot be rejected before it is sent.');
            } elseif ($locked->status === 'rejected' && $data['status'] === 'accepted') {
                abort(422, 'Rejected quotations must be sent again or revised before they can be accepted.');
            }
            abort_if(
                $locked->status === 'accepted' && $data['status'] !== 'accepted',
                422,
                'Accepted quotations can only be converted into invoices.',
            );
            $locked->update(['status' => $data['status']]);

            if ($data['status'] !== 'accepted') {
                return null;
            }

            $actor = $user->hasRole('customer') ? $locked->user : $user;

            return $this->issueAcceptedQuotation($locked, $actor, $stock);
        });

        if ($invoice !== null) {
            $emailSent = $this->sendBillingDocumentEmail(
                $invoice,
                $invoice->customer_email,
                $this->billingLocale($request),
                $chip,
                $paymentLinks,
            );

            return $this->invoiceEmailRedirect($invoice, $emailSent);
        }

        if ($data['status'] === 'sent' && ($data['delivery'] ?? null) === 'in_app') {
            $email = strtolower(trim((string) $document->customer_email));
            if ($email !== '') {
                User::role('customer')
                    ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
                    ->get()
                    ->each(fn (User $customer) => $customer->notify(new QuotationInAppNotification($document)));
            }
        }

        return redirect()
            ->route('billing.documents.show', $document)
            ->with('success', $data['status'] === 'sent' && ($data['delivery'] ?? null) === 'in_app'
                ? 'Quotation marked as sent and added to the customer panel.'
                : ($user->hasRole('customer') ? 'Thank you. Your quotation response has been recorded.' : 'Quotation status updated.'));
    }

    public function respondToQuotationFromEmail(
        Request $request,
        BillingDocument $document,
        string $status,
        InventoryStock $stock,
        ChipCollect $chip,
        GuestInvoicePaymentLinkService $paymentLinks,
    ): View {
        abort_unless($document->type === 'quotation', 404);
        abort_unless(in_array($status, ['accepted', 'rejected'], true), 404);
        abort_unless($document->status === 'sent', 422, 'This quotation has not been sent or has already been responded to.');

        if ($status === 'accepted') {
            $invoice = DB::transaction(function () use ($document, $stock): BillingDocument {
                $locked = BillingDocument::query()->lockForUpdate()->findOrFail($document->id);
                abort_unless(
                    $locked->type === 'quotation' && $locked->status === 'sent',
                    422,
                    'This quotation has not been sent or has already been responded to.',
                );
                $locked->update(['status' => 'accepted']);

                return $this->issueAcceptedQuotation($locked, $locked->user, $stock);
            });
            $invoiceEmailSent = $this->sendBillingDocumentEmail(
                $invoice,
                $invoice->customer_email,
                $this->billingLocale($request),
                $chip,
                $paymentLinks,
            );

            return view('billing.quotation-response', [
                'document' => $invoice,
                'status' => $status,
                'invoiceEmailSent' => $invoiceEmailSent,
            ]);
        }

        $document->update(['status' => $status]);

        return view('billing.quotation-response', [
            'document' => $document,
            'status' => $status,
            'invoiceEmailSent' => null,
        ]);
    }

    public function sendDocument(
        BillingDocument $document,
        Request $request,
        ChipCollect $chip,
        GuestInvoicePaymentLinkService $paymentLinks,
    ): RedirectResponse
    {
        abort_unless(! $request->user()->hasRole('customer'), 403);
        abort_unless(in_array($document->type, ['quotation', 'invoice'], true), 404);
        $allowedStatuses = $document->type === 'quotation'
            ? ['draft', 'sent', 'rejected']
            : ['issued', 'partial', 'part_paid', 'paid'];
        abort_unless(in_array($document->status, $allowedStatuses, true), 422, 'This billing document cannot be sent in its current status.');

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'locale' => ['nullable', Rule::in(array_keys(CustomerMessageTemplate::LANGUAGES))],
        ]);
        $locale = $data['locale'] ?? 'en';
        $emailSent = $this->sendBillingDocumentEmail(
            $document,
            $data['email'],
            $locale,
            $chip,
            $paymentLinks,
        );

        if ($document->type === 'quotation') {
            $document->update([
                'customer_email' => $data['email'],
                'status' => 'sent',
            ]);

            $matchingAccounts = User::role('customer')
                ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($data['email']))])
                ->get();
            $matchingAccounts->each(fn (User $customer) => $customer->notify(new QuotationInAppNotification($document)));
        }

        if (! $emailSent) {
            return back()->with('error', $document->type === 'quotation'
                ? 'Quotation was added to the customer panel, but the email could not be sent. Check the outgoing SMTP settings in Email Server Settings and try again.'
                : 'Invoice email could not be sent. Check the outgoing SMTP settings in Email Server Settings and try again.');
        }

        $delivery = ucfirst($document->type).' '.$document->number.' was emailed to '.$data['email'].'.';
        if ($document->type === 'quotation' && $matchingAccounts->isNotEmpty()) {
            $delivery = 'Quotation '.$document->number.' was sent to the customer panel and emailed to '.$data['email'].'.';
        }

        return back()->with('success', $delivery);
    }

    public function previewDocumentEmail(
        BillingDocument $document,
        Request $request,
        ChipCollect $chip,
        GuestInvoicePaymentLinkService $paymentLinks,
    ): Response
    {
        abort_unless(! $request->user()->hasRole('customer'), 403);
        abort_unless(in_array($document->type, ['quotation', 'invoice'], true), 404);

        $locale = $this->billingLocale($request);
        $document->load(['items', 'payments']);
        $paymentLinkAvailable = $document->type === 'invoice'
            && $chip->isConfigured()
            && $paymentLinks->isPayableInvoice($document)
            && (bool) filter_var($document->customer_email, FILTER_VALIDATE_EMAIL);
        $paymentUrl = $paymentLinkAvailable ? $paymentLinks->activeUrl($document, $locale) : null;
        $mail = $document->type === 'quotation'
            ? new QuotationMail($document, '', strtolower($document->number).'.pdf', true, $locale)
            : new InvoiceMail(
                $document,
                '',
                strtolower($document->number).'.pdf',
                true,
                $locale,
                $paymentUrl,
                $paymentLinkAvailable,
            );

        return response($mail->render(), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }

    public function previewReceiptEmail(BillingPayment $payment, Request $request): Response
    {
        abort_unless(! $request->user()->hasRole('customer'), 403);
        $payment->load('document.items');
        abort_unless($payment->document->type === 'invoice', 404);

        $mail = new BillingReceiptMail(
            $payment,
            '',
            true,
            $this->billingLocale($request),
        );

        return response($mail->render(), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }

    public function sendReceiptEmail(BillingPayment $payment, Request $request, BillingReceiptService $receipts): RedirectResponse
    {
        abort_unless(! $request->user()->hasRole('customer'), 403);
        $payment->load('document');
        abort_unless($payment->document->type === 'invoice', 404);
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'locale' => ['nullable', Rule::in(array_keys(CustomerMessageTemplate::LANGUAGES))],
        ]);

        $sent = $receipts->emailReceipt(
            $payment,
            locale: $data['locale'] ?? 'en',
            recipientEmail: $data['email'],
            resend: true,
        );
        if (! $sent) {
            return back()->with('error', 'The receipt could not be emailed. Check the email server settings and try again.');
        }

        return back()->with('success', 'Receipt '.$payment->receipt_number.' was emailed to '.$data['email'].'.');
    }

    public function storePayment(BillingDocument $document, Request $request, WorkspaceNotifier $notifier): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $request->validate([
            'amount' => ['required_without:payments', 'nullable', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999'],
            'method' => ['required_with:amount', 'nullable', Rule::in(BillingPayment::METHODS)],
            'payments' => ['required_without:amount', 'array', 'min:1', 'max:10'],
            'payments.*.amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999'],
            'payments.*.method' => ['required', Rule::in(BillingPayment::METHODS)],
            'reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['nullable', 'date', 'required_without:received_at'],
            'received_at' => ['nullable', 'date', 'required_without:paid_at'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'receipt_locale' => ['nullable', Rule::in(array_keys(CustomerMessageTemplate::LANGUAGES))],
            'receipt_delivery' => ['nullable', Rule::in(['send', 'preview'])],
        ]);

        $paymentLines = $data['payments'] ?? [[
            'amount' => $data['amount'],
            'method' => $data['method'],
        ]];
        $payments = DB::transaction(function () use ($document, $request, $data, $paymentLines): Collection {
            $locked = BillingDocument::query()->lockForUpdate()->findOrFail($document->id);
            abort_if($locked->type !== 'invoice' || ! in_array($locked->status, ['issued', 'partial', 'part_paid'], true), 422, 'This invoice cannot accept a payment.');
            $amountCents = array_sum(array_map(
                fn (array $line): int => (int) round((float) $line['amount'] * 100),
                $paymentLines,
            ));
            $balanceCents = (int) round(((float) $locked->total - (float) $locked->paid_amount) * 100);
            if ($amountCents > $balanceCents) {
                $field = array_key_exists('payments', $data) ? 'payments' : 'amount';
                $message = array_key_exists('payments', $data)
                    ? 'The combined payment amounts cannot exceed the outstanding balance.'
                    : 'The payment amount cannot exceed the outstanding balance.';
                throw ValidationException::withMessages([$field => $message]);
            }

            $payments = new Collection;
            foreach ($paymentLines as $line) {
                $payments->push($locked->payments()->create([
                    'amount' => round((float) $line['amount'], 2),
                    'method' => $line['method'],
                    'reference' => $data['reference'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'user_id' => $request->user()->id,
                    'paid_at' => $data['paid_at'] ?? $data['received_at'] ?? now(),
                ]));
            }
            $locked->paid_amount = round((float) $locked->paid_amount + ($amountCents / 100), 2);
            $locked->status = $locked->paid_amount >= $locked->total ? 'paid' : 'partial';
            $locked->save();

            return $payments;
        });

        $paymentTotal = $payments->sum(fn (BillingPayment $payment): float => (float) $payment->amount);
        $paymentMethods = $payments->pluck('method')->unique()->map(
            fn (string $method): string => __('billing.pdf.payment_method_'.$method),
        )->join(', ');
        $notifier->notifyAdmins(
            $request->user(),
            'Invoice payment received',
            sprintf(
                '%s received RM %s via %s.',
                $document->number,
                number_format($paymentTotal, 2),
                $paymentMethods,
            ),
            route('billing.documents.show', $document),
        );

        if (($data['receipt_delivery'] ?? 'send') === 'preview') {
            return redirect()
                ->route('billing.documents.show', $document)
                ->with('success', $payments->count() === 1
                    ? 'Payment recorded. Preview and send the receipt from payment history.'
                    : 'Payments recorded. Preview and send each receipt from payment history.');
        }

        $receiptService = app(BillingReceiptService::class);
        $sentCount = $payments->filter(fn (BillingPayment $payment): bool => $receiptService->emailReceipt(
            $payment,
            locale: $data['receipt_locale'] ?? app()->getLocale(),
        ))->count();
        $allReceiptsSent = $sentCount === $payments->count();
        $successMessage = $payments->count() === 1
            ? 'Payment recorded and the receipt was emailed to the customer.'
            : 'Payments recorded and receipts were emailed to the customer.';
        $failureMessage = $payments->count() === 1
            ? 'Payment recorded, but the receipt could not be emailed. It is still available to view and print.'
            : 'Payments recorded, but one or more receipts could not be emailed. They are still available to view and print.';

        return redirect()
            ->route('billing.documents.show', $document)
            ->with($allReceiptsSent ? 'success' : 'info', $allReceiptsSent ? $successMessage : $failureMessage);
    }

    public function void(BillingDocument $document, Request $request, InventoryStock $stock): RedirectResponse
    {
        DB::transaction(function () use ($document, $request, $stock): void {
            $locked = BillingDocument::query()->with('items')->lockForUpdate()->findOrFail($document->id);
            abort_if($locked->status === 'void' || (float) $locked->paid_amount > 0, 422, 'Paid or void documents cannot be voided.');

            if ($locked->type === 'invoice' && $locked->status === 'issued') {
                foreach ($locked->items as $item) {
                    if ($item->inventory_part_id) {
                        $stock->receive($item->part, $request->user(), $item->quantity, 'Stock returned from void invoice.', $locked->number);
                    }
                }
            }

            $locked->update(['status' => 'void']);
        });

        return back()->with('success', 'Document voided.');
    }

    public function pdf(Request $request, BillingDocument $document)
    {
        $this->authorizeCustomerDocument($request, $document);
        $document->load(['items', 'payments']);
        $filename = strtolower($document->number).'.pdf';
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response($this->renderPdf($document, $this->billingLocale($request)))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $disposition.'; filename="'.$filename.'"');
    }

    public function receiptPdf(Request $request, BillingPayment $payment, BillingReceiptService $receipts)
    {
        $document = BillingDocument::withoutGlobalScope('customer-recipient')
            ->findOrFail($payment->billing_document_id);
        abort_unless($document->type === 'invoice', 404);
        $this->authorizeCustomerDocument($request, $document);
        $payment->setRelation('document', $document);
        $payment->load('user');
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response($receipts->renderPdf($payment, $this->billingLocale($request)))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $disposition.'; filename="'.strtolower($payment->receipt_number).'.pdf"');
    }

    private function renderPdf(BillingDocument $document, ?string $locale = null): string
    {
        $locale = $locale && isset(CustomerMessageTemplate::LANGUAGES[$locale]) ? $locale : 'en';
        $tempDirectory = storage_path('app/mpdf-tmp');
        File::ensureDirectoryExists($tempDirectory);
        $logoPath = public_path('assets/img/omg/logoex2.png');
        $logoData = is_file($logoPath) ? base64_encode((string) file_get_contents($logoPath)) : null;
        $companyProfile = CompanyProfile::current();
        $originalLocale = app()->getLocale();
        app()->setLocale($locale);
        try {
            $html = view('billing.pdf', compact('document', 'logoData', 'companyProfile'))->render();
        } finally {
            app()->setLocale($originalLocale);
        }
        $mpdf = new Mpdf([
            'tempDir' => $tempDirectory,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output(strtolower($document->number).'.pdf', 'S');
    }

    private function billingLocale(Request $request): string
    {
        $data = $request->validate([
            'locale' => ['nullable', Rule::in(array_keys(CustomerMessageTemplate::LANGUAGES))],
        ]);
        $locale = $data['locale'] ?? app()->getLocale();

        return isset(CustomerMessageTemplate::LANGUAGES[$locale]) ? $locale : 'en';
    }

    private function guestPdfUrls(string $routeName, array $routeParameters): array
    {
        $expiresAt = now()->addDays(7);
        $urls = [];
        foreach (CustomerMessageTemplate::LANGUAGES as $locale => $label) {
            $urls[$locale] = URL::temporarySignedRoute(
                $routeName,
                $expiresAt,
                array_merge($routeParameters, ['locale' => $locale]),
            );
        }

        return $urls;
    }

    private function issueAcceptedQuotation(
        BillingDocument $document,
        ?User $actor,
        InventoryStock $stock,
    ): BillingDocument {
        return DB::transaction(function () use ($document, $actor, $stock): BillingDocument {
            $locked = BillingDocument::query()->with('items')->lockForUpdate()->findOrFail($document->id);
            abort_unless(
                $locked->type === 'quotation' && $locked->status === 'accepted',
                422,
                'Only accepted quotations can be converted to invoices.',
            );
            $locked->type = 'invoice';
            $locked->number = str_replace('QUO-', 'INV-', $locked->number);

            foreach ($locked->items as $item) {
                if ($item->inventory_part_id) {
                    abort_unless($actor !== null, 422, 'A staff account is required to deduct inventory for this invoice.');
                    $stock->useForSale($item->part, $actor, $item->quantity, $locked->number);
                }
            }

            $locked->status = 'issued';
            $locked->save();

            return $locked->load('payments');
        });
    }

    private function sendBillingDocumentEmail(
        BillingDocument $document,
        ?string $email,
        string $locale,
        ChipCollect $chip,
        GuestInvoicePaymentLinkService $paymentLinks,
    ): bool {
        $recipient = filter_var(trim((string) $email), FILTER_VALIDATE_EMAIL);
        if (! $recipient) {
            Log::warning('Billing document email could not be sent because no valid recipient is available.', [
                'document_id' => $document->id,
            ]);

            return false;
        }

        try {
            $mailSettings = app(MailSettingsService::class);
            $mailSettings->apply();
            $activeMailer = config('mail.default');
            if (in_array($activeMailer, ['log', 'array'], true)
                && ! ($activeMailer === 'array' && app()->environment('testing'))) {
                Log::warning('Billing document email was not sent because the active mailer does not deliver email.', [
                    'document_id' => $document->id,
                    'mailer' => $activeMailer,
                ]);

                return false;
            }

            $paymentUrl = null;
            if ($document->type === 'invoice'
                && $chip->isConfigured()
                && $paymentLinks->isPayableInvoice($document)
                && filter_var($document->customer_email, FILTER_VALIDATE_EMAIL)) {
                $paymentUrl = $paymentLinks->getOrCreate($document, $locale);
            }

            $document->load(['items', 'payments']);
            $pdf = $this->renderPdf($document, $locale);
            $mail = $document->type === 'quotation'
                ? new QuotationMail($document, $pdf, strtolower($document->number).'.pdf', false, $locale)
                : new InvoiceMail(
                    $document,
                    $pdf,
                    strtolower($document->number).'.pdf',
                    false,
                    $locale,
                    $paymentUrl,
                    $paymentUrl !== null,
                );
            Mail::to($recipient)->send($mail);

            return true;
        } catch (Throwable $exception) {
            Log::warning('Billing document email could not be sent.', [
                'document_id' => $document->id,
                'email' => $recipient,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function invoiceEmailRedirect(BillingDocument $invoice, bool $emailSent): RedirectResponse
    {
        if ($emailSent) {
            return redirect()
                ->route('billing.documents.show', $invoice)
                ->with('success', 'Invoice '.$invoice->number.' was created and emailed to '.$invoice->customer_email.'.');
        }

        return redirect()
            ->route('billing.documents.show', $invoice)
            ->with('error', 'Invoice '.$invoice->number.' was created, but the email could not be sent. Check the outgoing SMTP settings in Email Server Settings or use WhatsApp to share it.');
    }

    private function validateLineItems(array $items): void
    {
        foreach ($items as $index => $item) {
            if (! empty($item['inventory_part_id']) && ! empty($item['product_id'])) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'Choose either an inventory item or a service, not both.',
                ]);
            }
            if (! empty($item['custom_pc_component_id']) && (! empty($item['inventory_part_id']) || ! empty($item['product_id']))) {
                throw ValidationException::withMessages([
                    "items.{$index}.custom_pc_component_id" => 'Choose either a builder component, an inventory item, or a service.',
                ]);
            }
            if (empty($item['inventory_part_id']) && empty($item['custom_pc_component_id']) && empty($item['product_id']) && empty($item['description'])) {
                throw ValidationException::withMessages([
                    "items.{$index}.description" => 'Enter a description or select an inventory item.',
                ]);
            }

            if (empty($item['inventory_part_id']) && empty($item['custom_pc_component_id']) && empty($item['product_id']) && ! isset($item['unit_price'])) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_price" => 'Enter a unit price for this line.',
                ]);
            }
        }
    }
}
