<?php

namespace App\Http\Controllers;

use App\Models\BillingDocument;
use App\Models\InventoryPart;
use App\Models\RepairTicket;
use App\Services\InventoryStock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Mpdf\Mpdf;

class BillingController extends Controller
{
    public function index(Request $request): View
    {
        $query = BillingDocument::query()->withSum('payments', 'amount');

        if ($request->filled('type') && in_array($request->input('type'), ['invoice', 'quotation'], true)) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('status') && in_array($request->input('status'), ['draft', 'issued', 'partial', 'part_paid', 'paid', 'void'], true)) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn ($documents) => $documents
                ->where('number', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('customer_email', 'like', "%{$search}%"));
        }

        return view('billing.index', [
            'documents' => $query->latest('issue_date')->paginate(20)->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        $type = in_array($request->input('type'), ['quotation', 'quote'], true) ? 'quotation' : 'invoice';
        $ticket = $request->filled('repair_ticket_id')
            ? RepairTicket::query()->with('customer')->findOrFail($request->integer('repair_ticket_id'))
            : null;

        return view('billing.create', [
            'parts' => InventoryPart::query()->orderBy('name')->get(),
            'products' => collect(config('products.catalog')),
            'ticket' => $ticket,
            'document' => new BillingDocument([
                'type' => $type,
                'customer_name' => $ticket?->customer->name,
                'customer_email' => $ticket?->customer->email,
                'customer_phone' => $ticket?->customer->phone,
                'customer_address' => $ticket?->customer->address,
                'issue_date' => now()->toDateString(),
                'notes' => $ticket ? "Repair ticket {$ticket->ticket_number}: {$ticket->issue_description}" : null,
            ]),
            'lines' => [[
                'inventory_part_id' => null,
                'product_id' => null,
                'description' => $ticket ? 'Repair service: ' . $ticket->issue_description : '',
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
        abort_unless($document->status === 'draft', 404);
        $document->load('items');

        return view('billing.create', [
            'parts' => InventoryPart::query()->orderBy('name')->get(),
            'products' => collect(config('products.catalog')),
            'ticket' => $document->repairTicket,
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

    public function store(Request $request, InventoryStock $stock): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:invoice,quotation,quote'],
            'repair_ticket_id' => ['nullable', 'integer', 'exists:repair_tickets,id'],
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
            'items.*.product_id' => ['nullable', 'string', Rule::in(collect(config('products.catalog'))->pluck('id')->all())],
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
                'number' => 'TEMP-' . Str::upper(Str::random(12)),
                'type' => $type,
                'status' => 'draft',
                'user_id' => $request->user()->id,
            'repair_ticket_id' => $data['repair_ticket_id'] ?? null,
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
                    ? collect(config('products.catalog'))->firstWhere('id', $item['product_id'])
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
                    . now()->format('Y') . '-' . str_pad((string) $document->id, 6, '0', STR_PAD_LEFT),
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
        abort_unless($document->status === 'draft', 404);
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
            'items.*.product_id' => ['nullable', 'string', Rule::in(collect(config('products.catalog'))->pluck('id')->all())],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $this->validateLineItems($data['items']);
        if (! empty($data['due_date']) && $data['due_date'] < $data['issue_date']) {
            throw ValidationException::withMessages(['due_date' => 'The due date must be on or after the issue date.']);
        }

        DB::transaction(function () use ($document, $data): void {
            $locked = BillingDocument::query()->lockForUpdate()->findOrFail($document->id);
            abort_unless($locked->status === 'draft', 404);
            $locked->update([
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_address' => $data['customer_address'] ?? null,
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
                'tax_rate' => $data['tax_rate'],
                'notes' => $data['notes'] ?? null,
            ]);
            $locked->items()->delete();

            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $part = ! empty($item['inventory_part_id'])
                    ? InventoryPart::query()->findOrFail($item['inventory_part_id'])
                    : null;
                $product = ! empty($item['product_id'])
                    ? collect(config('products.catalog'))->firstWhere('id', $item['product_id'])
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

        return redirect()->route('billing.documents.show', $document)->with('success', 'Draft billing document updated.');
    }

    public function show(BillingDocument $document): View
    {
        $document->load(['items.part', 'payments' => fn ($query) => $query->latest('paid_at')]);

        return view('billing.show', compact('document'));
    }

    public function issue(BillingDocument $document, Request $request, InventoryStock $stock): RedirectResponse
    {
        DB::transaction(function () use ($document, $request, $stock): void {
            $document = BillingDocument::query()->with('items')->lockForUpdate()->findOrFail($document->id);
            abort_if($document->status !== 'draft', 422, 'Only draft documents can be issued.');

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

    public function storePayment(BillingDocument $document, Request $request): RedirectResponse
    {
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

    public function pdf(BillingDocument $document)
    {
        $document->load(['items', 'payments']);
        $tempDirectory = storage_path('app/mpdf-tmp');
        File::ensureDirectoryExists($tempDirectory);
        $logoPath = public_path('assets/img/omg/logoex2.png');
        $logoData = is_file($logoPath) ? base64_encode((string) file_get_contents($logoPath)) : null;
        $mpdf = new Mpdf(['tempDir' => $tempDirectory]);
        $mpdf->WriteHTML(view('billing.pdf', compact('document', 'logoData'))->render());
        $filename = strtolower($document->number) . '.pdf';

        return response($mpdf->Output($filename, 'S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
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