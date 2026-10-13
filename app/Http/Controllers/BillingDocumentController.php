<?php

namespace App\Http\Controllers;

use App\Models\BillingDocument;
use App\Models\InventoryPart;
use App\Services\InventoryStock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class BillingDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $type = in_array($request->query('type'), ['quote', 'invoice'], true)
            ? $request->query('type')
            : null;
        $status = in_array($request->query('status'), ['draft', 'issued', 'partial', 'paid', 'void'], true)
            ? $request->query('status')
            : null;

        $documents = BillingDocument::query()
            ->with('creator:id,name')
            ->withSum('payments', 'amount')
            ->withCount('items')
            ->when($type !== null, fn (Builder $query): Builder => $query->where('type', $type))
            ->when($status !== null, fn (Builder $query): Builder => $query->where('status', $status))
            ->latest('issued_at')
            ->latest('id')
            ->get();

        return view('billing.index', [
            'documents' => $documents,
            'type' => $type,
            'status' => $status,
        ]);
    }

    public function create(Request $request): View
    {
        $type = in_array($request->query('type'), ['quote', 'invoice'], true)
            ? $request->query('type')
            : 'quote';

        return view('billing.create', [
            'type' => $type,
            'parts' => InventoryPart::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:quote,invoice'],
            'customer_name' => ['required', 'string', 'max:180'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'issued_at' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'items.*.inventory_part_id' => ['nullable', 'integer', Rule::exists('inventory_parts', 'id')->where('is_active', true)],
        ]);

        $document = DB::transaction(function () use ($request, $validated): BillingDocument {
            $subtotal = 0;
            $lineItems = [];

            foreach ($validated['items'] as $item) {
                $lineTotal = round((int) $item['quantity'] * (float) $item['unit_price'], 2);
                $subtotal += $lineTotal;
                $lineItems[] = [
                    'inventory_part_id' => $item['inventory_part_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => round((float) $item['unit_price'], 2),
                    'line_total' => $lineTotal,
                ];
            }

            $subtotal = round($subtotal, 2);
            $taxRate = round((float) $validated['tax_rate'], 2);
            $taxAmount = round($subtotal * $taxRate / 100, 2);
            $document = BillingDocument::create([
                'user_id' => $request->user()->id,
                'document_number' => 'TEMP-'.Str::uuid(),
                'type' => $validated['type'],
                'status' => $validated['type'] === 'invoice' ? 'issued' : 'draft',
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'issued_at' => $validated['issued_at'],
                'due_date' => $validated['due_date'] ?? null,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => round($subtotal + $taxAmount, 2),
                'notes' => $validated['notes'] ?? null,
                'stock_committed_at' => $validated['type'] === 'invoice' ? now() : null,
            ]);

            $document->update([
                'document_number' => sprintf('%s-%s-%06d', strtoupper($validated['type'] === 'quote' ? 'Q' : 'INV'), now()->format('Y'), $document->id),
            ]);
            $document->items()->createMany($lineItems);

            if ($validated['type'] === 'invoice') {
                $this->deductPartsForDocument($document, $request, app(InventoryStock::class));
            }

            return $document;
        });

        return redirect()
            ->route('billing.documents.show', $document)
            ->with('status', ucfirst($document->type).' created successfully.');
    }

    public function show(BillingDocument $billingDocument): View
    {
        $billingDocument->load(['creator:id,name', 'items.part:id,sku,name', 'payments.user:id,name']);
        $billingDocument->loadSum('payments', 'amount');

        return view('billing.show', ['document' => $billingDocument]);
    }

    public function pdf(BillingDocument $billingDocument)
    {
        $billingDocument->load(['creator:id,name', 'items', 'payments']);
        $html = view('billing.pdf', ['document' => $billingDocument])->render();
        $temporaryDirectory = storage_path('app/mpdf-tmp');
        File::ensureDirectoryExists($temporaryDirectory);

        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $temporaryDirectory,
            'default_font' => 'dejavusans',
        ]);
        $pdf->SetTitle($billingDocument->document_number);
        $pdf->WriteHTML($html);

        return response($pdf->Output($billingDocument->document_number.'.pdf', Destination::STRING_RETURN))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$billingDocument->document_number.'.pdf"');
    }

    public function convertQuote(Request $request, BillingDocument $billingDocument, InventoryStock $stock): RedirectResponse
    {
        abort_unless($billingDocument->type === 'quote' && $billingDocument->status !== 'void', 404);

        DB::transaction(function () use ($request, $billingDocument, $stock): void {
            $lockedDocument = BillingDocument::query()->lockForUpdate()->findOrFail($billingDocument->id);
            abort_unless($lockedDocument->type === 'quote' && $lockedDocument->status !== 'void', 404);

            $lockedDocument->update([
                'type' => 'invoice',
                'status' => 'issued',
                'document_number' => sprintf('INV-%s-%06d', now()->format('Y'), $lockedDocument->id),
                'stock_committed_at' => now(),
            ]);
            $this->deductPartsForDocument($lockedDocument, $request, $stock);
        });

        return redirect()
            ->route('billing.documents.show', $billingDocument)
            ->with('status', 'Quote converted to an invoice and inventory updated.');
    }

    public function payment(Request $request, BillingDocument $billingDocument): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'method' => ['required', 'in:cash,bank_transfer,card,online,other'],
            'reference' => ['nullable', 'string', 'max:120'],
            'received_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $billingDocument, $validated): void {
            $document = BillingDocument::query()->lockForUpdate()->findOrFail($billingDocument->id);
            abort_unless($document->type === 'invoice' && in_array($document->status, ['issued', 'partial'], true), 422);

            $paid = (float) $document->payments()->sum('amount');
            $amount = round((float) $validated['amount'], 2);
            if ($amount > round((float) $document->total - $paid, 2)) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment cannot be greater than the invoice balance.',
                ]);
            }

            $document->payments()->create([
                'user_id' => $request->user()->id,
                'amount' => $amount,
                'method' => $validated['method'],
                'reference' => $validated['reference'] ?? null,
                'received_at' => $validated['received_at'],
                'note' => $validated['note'] ?? null,
            ]);

            $document->update([
                'status' => round($paid + $amount, 2) >= (float) $document->total ? 'paid' : 'partial',
            ]);
        });

        return redirect()
            ->route('billing.documents.show', $billingDocument)
            ->with('status', 'Payment recorded.');
    }

    private function deductPartsForDocument(BillingDocument $document, Request $request, InventoryStock $stock): void
    {
        $quantities = $document->items()
            ->whereNotNull('inventory_part_id')
            ->selectRaw('inventory_part_id, SUM(quantity) as quantity')
            ->groupBy('inventory_part_id')
            ->get();

        foreach ($quantities as $partQuantity) {
            $part = InventoryPart::query()->findOrFail($partQuantity->inventory_part_id);
            $stock->useForSale($part, $request->user(), (int) $partQuantity->quantity, $document->document_number);
        }
    }
}
