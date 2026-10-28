<?php

namespace App\Http\Controllers;

use App\Models\BillingDocument;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\InventoryPart;
use App\Models\ProductService;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\User;
use App\Services\InventoryStock;
use App\Support\DataTableQueryBuilder;
use App\Support\StatusBadge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\QuotationMail;
use App\Services\MailSettingsService;
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
            ->editColumn('paid_amount', fn (BillingDocument $document): string => 'RM '.number_format((float) $document->paid_amount, 2))
            ->addColumn('balance', fn (BillingDocument $document): string => 'RM '.number_format($document->balance, 2))
            ->rawColumns(['document', 'status_label'])
            ->toJson();
    }

    public function create(Request $request): View
    {
        $type = in_array($request->input('type'), ['quotation', 'quote'], true) ? 'quotation' : 'invoice';
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

        return view('billing.create', [
            'parts' => InventoryPart::query()->orderBy('name')->get(),
            'products' => ProductService::query()->orderBy('name')->get(),
            'ticket' => $ticket,
            'project' => $project,
            'projectRecipients' => $projectRecipients,
            'selectedRecipientKey' => $selectedRecipientKey,
            'customerRecipients' => $this->customerRecipients(),
            'selectedCustomerRecipientKey' => $this->selectedCustomerRecipientKey($customer),
            'document' => new BillingDocument([
                'type' => $type,
                'customer_name' => $customer?->name,
                'customer_email' => $customer?->email,
                'customer_phone' => $customer?->phone,
                'customer_address' => $customer?->address,
                'issue_date' => now()->toDateString(),
                'tax_rate' => config('billing.tax_rate', 6),
                'notes' => $ticket ? "Repair ticket {$ticket->ticket_number}: {$ticket->issue_description}" : null,
            ]),
            'lines' => [[
                'inventory_part_id' => null,
                'product_id' => null,
                'description' => $ticket ? 'Repair service: '.$ticket->issue_description : ($project ? 'Project: '.$project->name : ''),
                'quantity' => 1,
                'unit_price' => null,
            ]],
            'type' => $type,
            'formAction' => route('billing.store'),
            'editing' => false,
        ]);
    }

    public function edit(BillingDocument $document): View
    {
        abort_unless(
            $document->status === 'draft'
                || ($document->type === 'quotation' && $document->status === 'rejected'),
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

        return view('billing.create', [
            'parts' => InventoryPart::query()->orderBy('name')->get(),
            'products' => ProductService::query()->orderBy('name')->get(),
            'ticket' => $document->repairTicket,
            'project' => $document->project,
            'projectRecipients' => $projectRecipients,
            'selectedRecipientKey' => $selectedRecipientKey,
            'customerRecipients' => $this->customerRecipients(),
            'selectedCustomerRecipientKey' => $this->selectedCustomerRecipientKey(null, $document->customer_email),
            'document' => $document,
            'lines' => $document->items->map(fn ($item) => [
                'inventory_part_id' => $item->inventory_part_id,
                'product_id' => null,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ])->all(),
            'type' => $document->type,
            'formAction' => route('billing.documents.update', $document),
            'editing' => true,
        ]);
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

    private function selectedCustomerRecipientKey(?Customer $customer, ?string $email = null): string
    {
        $recipientEmail = strtolower(trim($customer?->email ?? $email ?? ''));
        if ($recipientEmail === '') {
            return '';
        }

        foreach ($this->customerRecipients() as $recipient) {
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

    public function store(Request $request, InventoryStock $stock): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:invoice,quotation,quote'],
            'repair_ticket_id' => ['nullable', 'integer', 'exists:repair_tickets,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:64'],
            'customer_address' => ['nullable', 'string', 'max:2000'],
            'issue_date' => ['nullable', 'date'],
            'issued_at' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.inventory_part_id' => ['nullable', 'integer', 'exists:inventory_parts,id'],
            'items.*.product_id' => ['nullable', 'string', Rule::exists('product_services', 'id')],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ]);

        foreach ($data['items'] as $index => $item) {
            if (! empty($item['inventory_part_id']) && ! empty($item['product_id'])) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'Choose either an inventory item or a service, not both.',
                ]);
            }
            if (empty($item['inventory_part_id']) && empty($item['product_id']) && empty($item['description'])) {
                throw ValidationException::withMessages([
                    "items.{$index}.description" => 'Enter a description or select an inventory item.',
                ]);
            }
            if (empty($item['inventory_part_id']) && empty($item['product_id']) && ! isset($item['unit_price'])) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_price" => 'Enter a unit price for this line.',
                ]);
            }
        }

        $type = $data['type'] === 'quote' ? 'quotation' : $data['type'];
        $issueDate = $data['issue_date'] ?? $data['issued_at'] ?? now()->toDateString();
        if (! empty($data['due_date']) && $data['due_date'] < $issueDate) {
            throw ValidationException::withMessages(['due_date' => 'The due date must be on or after the issue date.']);
        }

        $document = DB::transaction(function () use ($data, $request, $type, $issueDate, $stock): BillingDocument {
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
                'tax_rate' => $data['tax_rate'],
                'notes' => $data['notes'] ?? null,
            ]);

            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $part = ! empty($item['inventory_part_id'])
                    ? InventoryPart::query()->findOrFail($item['inventory_part_id'])
                    : null;
                $product = ! empty($item['product_id'])
                    ? ProductService::withTrashed()->findOrFail($item['product_id'])
                    : null;
                $price = round((float) ($item['unit_price'] ?? $part?->selling_price ?? $product['billing_price'] ?? 0), 2);
                $lineTotal = round($price * (int) $item['quantity'], 2);
                $subtotal += $lineTotal;

                $document->items()->create([
                    'inventory_part_id' => $part?->id,
                    'description' => $part?->name ?? $product['name'] ?? $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $price,
                    'line_total' => $lineTotal,
                ]);
            }

            $taxAmount = round($subtotal * (float) $data['tax_rate'] / 100, 2);
            $document->update([
                'number' => ($type === 'quotation' ? 'QUO-' : 'INV-')
                    .now()->format('Y').'-'.str_pad((string) $document->id, 6, '0', STR_PAD_LEFT),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => round($subtotal + $taxAmount, 2),
            ]);

            if ($type === 'invoice') {
                foreach ($document->items as $item) {
                    if ($item->inventory_part_id) {
                        $stock->useForSale($item->part, $request->user(), $item->quantity, $document->number);
                    }
                }
                $document->update(['status' => 'issued']);
            }

            return $document;
        });

        return redirect()->route('billing.documents.show', $document)->with('success', $type === 'invoice' ? 'Invoice issued.' : 'Quotation saved as a draft.');
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
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.inventory_part_id' => ['nullable', 'integer', 'exists:inventory_parts,id'],
            'items.*.product_id' => ['nullable', 'string', Rule::exists('product_services', 'id')],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $this->validateLineItems($data['items']);
        if (! empty($data['due_date']) && $data['due_date'] < $data['issue_date']) {
            throw ValidationException::withMessages(['due_date' => 'The due date must be on or after the issue date.']);
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
                'tax_rate' => $data['tax_rate'],
                'notes' => $data['notes'] ?? null,
                ...($wasRejectedQuotation ? ['status' => 'draft'] : []),
            ]);
            $locked->items()->delete();

            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $part = ! empty($item['inventory_part_id'])
                    ? InventoryPart::query()->findOrFail($item['inventory_part_id'])
                    : null;
                $product = ! empty($item['product_id'])
                    ? ProductService::withTrashed()->findOrFail($item['product_id'])
                    : null;
                $price = round((float) ($item['unit_price'] ?? $part?->selling_price ?? $product['billing_price'] ?? 0), 2);
                $lineTotal = round($price * (int) $item['quantity'], 2);
                $subtotal += $lineTotal;
                $locked->items()->create([
                    'inventory_part_id' => $part?->id,
                    'description' => $part?->name ?? $product['name'] ?? $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $price,
                    'line_total' => $lineTotal,
                ]);
            }

            $taxAmount = round($subtotal * (float) $data['tax_rate'] / 100, 2);
            $locked->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => round($subtotal + $taxAmount, 2),
            ]);
        });

        return redirect()->route('billing.documents.show', $document)->with(
            'success',
            $document->type === 'quotation' && $document->status === 'rejected'
                ? 'Rejected quotation updated and returned to draft. Mark it as sent when ready.'
                : 'Draft billing document updated.',
        );
    }

    public function show(BillingDocument $document): View
    {
        $this->authorizeCustomerDocument(request(), $document);
        $document->load([
            'items.part',
            'payments' => fn ($query) => $query->latest('paid_at'),
            'project.customers',
            'repairTicket.customer',
            'repairTicket.project.customers',
        ]);
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

        return view('billing.show', compact('document', 'customerRecord'));
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
        $email = strtolower(trim($user->email));
        if ($email === '') {
            return BillingDocument::query()->whereRaw('1 = 0');
        }

        return BillingDocument::query()
            ->whereIn('type', ['invoice', 'quotation'])
            ->where('status', '!=', 'draft')
            ->whereRaw('LOWER(TRIM(customer_email)) = ?', [$email])
            ->where(function (Builder $documents) use ($user, $email): void {
                $documents->whereHas('project', fn (Builder $projects) => $projects->visibleToCustomer($user))
                    ->orWhereHas('repairTicket.project', fn (Builder $projects) => $projects->visibleToCustomer($user))
                    ->orWhereHas('repairTicket.customer', fn (Builder $customers) => $customers
                        ->whereRaw('LOWER(TRIM(email)) = ?', [$email]));
            });
    }

    public function issue(BillingDocument $document, Request $request, InventoryStock $stock): RedirectResponse
    {
        DB::transaction(function () use ($document, $request, $stock): void {
            $document = BillingDocument::query()->with('items')->lockForUpdate()->findOrFail($document->id);
            abort_if(
                $document->type === 'quotation'
                    ? $document->status !== 'accepted'
                    : $document->status !== 'draft',
                422,
                $document->type === 'quotation'
                    ? 'Only accepted quotations can be converted to invoices.'
                    : 'Only draft invoices can be issued.',
            );

            if ($document->type === 'quotation') {
                $document->type = 'invoice';
                $document->number = str_replace('QUO-', 'INV-', $document->number);
            }

            foreach ($document->items as $item) {
                if ($item->inventory_part_id) {
                    $stock->useForSale($item->part, $request->user(), $item->quantity, $document->number);
                }
            }

            $document->status = 'issued';
            $document->save();
        });

        return redirect()->route('billing.documents.show', $document)->with('success', 'Document issued; inventory was updated.');
    }

    public function updateQuotationStatus(BillingDocument $document, Request $request): RedirectResponse
    {
        abort_unless(! $request->user()->hasRole('customer'), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(['sent', 'accepted', 'rejected'])],
        ]);

        DB::transaction(function () use ($document, $data): void {
            $locked = BillingDocument::query()->lockForUpdate()->findOrFail($document->id);
            abort_unless($locked->type === 'quotation', 404);
            abort_if($locked->status === 'void', 422, 'Voided quotations cannot be changed.');
            abort_if(
                $locked->status === 'draft' && $data['status'] !== 'sent',
                422,
                'Draft quotations must be marked as sent before changing their status.',
            );
            abort_if(
                $locked->status === 'accepted' && $data['status'] !== 'accepted',
                422,
                'Accepted quotations can only be converted into invoices.',
            );
            abort_if(
                $locked->status === 'rejected' && $data['status'] === 'accepted',
                422,
                'Rejected quotations must be sent again before they can be accepted.',
            );

            $locked->update(['status' => $data['status']]);
        });

        return redirect()
            ->route('billing.documents.show', $document)
            ->with('success', 'Quotation status updated.');
    }

    public function sendQuotation(BillingDocument $document, Request $request): RedirectResponse
    {
        abort_unless(! $request->user()->hasRole('customer'), 403);
        abort_unless($document->type === 'quotation', 404);
        abort_unless(in_array($document->status, ['draft', 'sent', 'rejected'], true), 422, 'This quotation cannot be sent in its current status.');

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        try {
            app(MailSettingsService::class)->apply();
            $document->load(['items', 'payments']);
            $pdf = $this->renderPdf($document);
            Mail::to($data['email'])->send(new QuotationMail(
                $document,
                $pdf,
                strtolower($document->number).'.pdf',
            ));
        } catch (Throwable $exception) {
            Log::warning('Quotation email could not be sent.', [
                'document_id' => $document->id,
                'email' => $data['email'],
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return back()->with('error', 'The quotation email could not be sent. Check the email server settings and try again.');
        }

        $document->update([
            'customer_email' => $data['email'],
            'status' => 'sent',
        ]);

        return back()->with('success', 'Quotation '.$document->number.' was sent to '.$data['email'].'.');
    }

    public function storePayment(BillingDocument $document, Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'method' => ['required', 'in:cash,card,bank_transfer,online,other'],
            'reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['nullable', 'date', 'required_without:received_at'],
            'received_at' => ['nullable', 'date', 'required_without:paid_at'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($document, $request, $data): void {
            $locked = BillingDocument::query()->lockForUpdate()->findOrFail($document->id);
            abort_if($locked->type !== 'invoice' || ! in_array($locked->status, ['issued', 'partial', 'part_paid'], true), 422, 'This invoice cannot accept a payment.');
            $amount = round((float) $data['amount'], 2);
            $balance = round((float) $locked->total - (float) $locked->paid_amount, 2);
            if ($amount > $balance) {
                throw ValidationException::withMessages(['amount' => 'Payment cannot exceed the outstanding balance.']);
            }

            $locked->payments()->create([
                'amount' => $amount,
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'user_id' => $request->user()->id,
                'paid_at' => $data['paid_at'] ?? $data['received_at'] ?? now(),
            ]);
            $locked->paid_amount = round((float) $locked->paid_amount + $amount, 2);
            $locked->status = $locked->paid_amount >= $locked->total ? 'paid' : 'partial';
            $locked->save();
        });

        return redirect()->route('billing.documents.show', $document)->with('success', 'Payment recorded.');
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

        return response($this->renderPdf($document))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$filename.'"');
    }

    private function renderPdf(BillingDocument $document): string
    {
        $tempDirectory = storage_path('app/mpdf-tmp');
        File::ensureDirectoryExists($tempDirectory);
        $logoPath = public_path('assets/img/omg/logoex2.png');
        $logoData = is_file($logoPath) ? base64_encode((string) file_get_contents($logoPath)) : null;
        $companyProfile = CompanyProfile::current();
        $mpdf = new Mpdf(['tempDir' => $tempDirectory]);
        $mpdf->WriteHTML(view('billing.pdf', compact('document', 'logoData', 'companyProfile'))->render());

        return $mpdf->Output(strtolower($document->number).'.pdf', 'S');
    }

    private function validateLineItems(array $items): void
    {
        foreach ($items as $index => $item) {
            if (! empty($item['inventory_part_id']) && ! empty($item['product_id'])) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'Choose either an inventory item or a service, not both.',
                ]);
            }
            if (empty($item['inventory_part_id']) && empty($item['product_id']) && empty($item['description'])) {
                throw ValidationException::withMessages([
                    "items.{$index}.description" => 'Enter a description or select an inventory item.',
                ]);
            }
            if (empty($item['inventory_part_id']) && empty($item['product_id']) && ! isset($item['unit_price'])) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_price" => 'Enter a unit price for this line.',
                ]);
            }
        }
    }
}
