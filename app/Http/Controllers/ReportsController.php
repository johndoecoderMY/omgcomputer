<?php

namespace App\Http\Controllers;

use App\Models\BillingDocument;
use App\Models\BillingPayment;
use App\Models\InventoryMovement;
use App\Models\InventoryPart;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to] = $this->dateRange($request);
        $start = $from->startOfDay();
        $end = $to->endOfDay();

        $invoices = BillingDocument::query()
            ->where('type', 'invoice')
            ->whereIn('status', ['issued', 'partial', 'paid'])
            ->whereBetween('issued_at', [$from->toDateString(), $to->toDateString()]);
        $payments = BillingPayment::query()
            ->whereBetween('received_at', [$from->toDateString(), $to->toDateString()]);

        $invoiceTotals = (clone $invoices)
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COALESCE(SUM(total), 0) as billed')
            ->first();
        $paymentTotal = (float) (clone $payments)->sum('amount');
        $outstanding = (float) BillingDocument::query()
            ->where('type', 'invoice')
            ->whereIn('status', ['issued', 'partial'])
            ->selectRaw('COALESCE(SUM(total - (SELECT COALESCE(SUM(amount), 0) FROM billing_payments WHERE billing_payments.billing_document_id = billing_documents.id)), 0) as balance')
            ->value('balance');

        $monthly = collect();
        $monthCursor = $from->startOfMonth();
        $lastMonth = $to->startOfMonth();
        while ($monthCursor <= $lastMonth) {
            $monthStart = $monthCursor->startOfMonth()->toDateString();
            $monthEnd = $monthCursor->endOfMonth()->toDateString();
            $monthly->push([
                'label' => $monthCursor->format('M Y'),
                'billed' => (float) BillingDocument::query()
                    ->where('type', 'invoice')
                    ->whereIn('status', ['issued', 'partial', 'paid'])
                    ->whereBetween('issued_at', [$monthStart, $monthEnd])
                    ->sum('total'),
                'collected' => (float) BillingPayment::query()
                    ->whereBetween('received_at', [$monthStart, $monthEnd])
                    ->sum('amount'),
            ]);
            $monthCursor = $monthCursor->addMonth();
        }
        $maxMonthly = max(1, (float) $monthly->max('billed'), (float) $monthly->max('collected'));

        $topServices = DB::table('billing_document_items')
            ->join('billing_documents', 'billing_documents.id', '=', 'billing_document_items.billing_document_id')
            ->where('billing_documents.type', 'invoice')
            ->whereIn('billing_documents.status', ['issued', 'partial', 'paid'])
            ->whereBetween('billing_documents.issued_at', [$from->toDateString(), $to->toDateString()])
            ->select('billing_document_items.description')
            ->selectRaw('SUM(billing_document_items.quantity) as units')
            ->selectRaw('SUM(billing_document_items.line_total) as sales')
            ->groupBy('billing_document_items.description')
            ->orderByDesc('sales')
            ->limit(8)
            ->get();

        $lowStock = InventoryPart::query()
            ->where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'reorder_level')
            ->orderBy('stock_quantity')
            ->limit(10)
            ->get(['sku', 'name', 'stock_quantity', 'reorder_level']);

        $stockMovementTotals = InventoryMovement::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(CASE WHEN quantity_change > 0 THEN quantity_change ELSE 0 END), 0) as received')
            ->selectRaw('COALESCE(SUM(CASE WHEN quantity_change < 0 THEN ABS(quantity_change) ELSE 0 END), 0) as used')
            ->first();

        return view('reports.index', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'invoiceCount' => (int) $invoiceTotals->count,
            'billed' => (float) $invoiceTotals->billed,
            'collected' => $paymentTotal,
            'outstanding' => $outstanding,
            'monthly' => $monthly,
            'maxMonthly' => $maxMonthly,
            'topServices' => $topServices,
            'lowStock' => $lowStock,
            'stockReceived' => (int) $stockMovementTotals->received,
            'stockUsed' => (int) $stockMovementTotals->used,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->dateRange($request);

        $documents = BillingDocument::query()
            ->withSum('payments', 'amount')
            ->where('type', 'invoice')
            ->whereIn('status', ['issued', 'partial', 'paid'])
            ->whereBetween('issued_at', [$from->toDateString(), $to->toDateString()])
            ->orderBy('issued_at')
            ->get();

        return response()->streamDownload(function () use ($documents): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \RuntimeException('Unable to open report output stream.');
            }

            fputcsv($output, ['Invoice', 'Customer', 'Issued', 'Status', 'Total (RM)', 'Paid (RM)', 'Balance (RM)']);
            foreach ($documents as $document) {
                $paid = (float) $document->payments_sum_amount;
                fputcsv($output, [
                    $this->safeCsvCell($document->document_number),
                    $this->safeCsvCell($document->customer_name),
                    $document->issued_at->toDateString(),
                    $this->safeCsvCell($document->status),
                    number_format((float) $document->total, 2, '.', ''),
                    number_format($paid, 2, '.', ''),
                    number_format(max(0, (float) $document->total - $paid), 2, '.', ''),
                ]);
            }

            fclose($output);
        }, "business-report-{$from->toDateString()}-to-{$to->toDateString()}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function safeCsvCell(string $value): string
    {
        return preg_match('/^[\s]*[=+\-@]/u', $value) === 1 ? "'".$value : $value;
    }

    private function dateRange(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = isset($validated['from'])
            ? CarbonImmutable::parse($validated['from'])
            : CarbonImmutable::now()->startOfMonth();
        $to = isset($validated['to'])
            ? CarbonImmutable::parse($validated['to'])
            : CarbonImmutable::now()->endOfMonth();

        if ($from->diffInDays($to) > 366) {
            throw ValidationException::withMessages([
                'to' => 'Choose a reporting period of one year or less.',
            ]);
        }

        return [$from, $to];
    }
}
