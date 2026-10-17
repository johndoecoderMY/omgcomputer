<?php

namespace App\Http\Controllers;

use App\Models\BillingDocument;
use App\Models\BillingDocumentItem;
use App\Models\CustomerMessageTemplate;
use App\Models\InventoryMovement;
use App\Models\InventoryPart;
use App\Models\MarketingCampaign;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to] = $this->period($request);
        $documents = $this->salesDocuments()->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()]);

        return view('reports.index', [
            'salesTotal' => (clone $documents)->sum('total'),
            'invoiceCount' => (clone $documents)->count(),
            'billed' => (clone $documents)->sum('total'),
            'openBalance' => (clone $documents)->whereIn('status', ['issued', 'partial', 'part_paid'])->sum('total')
                - (clone $documents)->whereIn('status', ['issued', 'partial', 'part_paid'])->sum('paid_amount'),
            'from' => $from,
            'to' => $to,
            'lowStockCount' => InventoryPart::query()->whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
            'activeCampaigns' => MarketingCampaign::query()->where('status', 'active')->count(),
            'deliverySetup' => [
                'api_key' => filled(config('services.delivery.api_key')),
                'api_url' => filled(config('services.delivery.api_url')),
            ],
            'deliveryEnvironment' => config('services.delivery.environment'),
        ]);
    }

    public function sales(Request $request): View
    {
        [$from, $to] = $this->period($request);
        $documents = $this->salesDocuments()->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()]);
        $invoices = (clone $documents)->with('payments')->latest('issue_date')->get();
        $topItems = BillingDocumentItem::query()
            ->selectRaw('billing_document_items.description, SUM(billing_document_items.quantity) as units, SUM(billing_document_items.line_total) as revenue')
            ->whereHas('document', fn ($query) => $query->where('type', 'invoice')
                ->whereIn('status', ['issued', 'part_paid', 'paid'])
                ->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()]))
            ->groupBy('billing_document_items.description')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();
        $salesTrend = $invoices
            ->groupBy(fn (BillingDocument $invoice): string => $invoice->issue_date->format('Y-m'))
            ->sortKeys()
            ->map(fn ($monthlyInvoices, string $month): array => [
                'month' => Carbon::createFromFormat('Y-m', $month)->format('M Y'),
                'revenue' => round((float) $monthlyInvoices->sum('total'), 2),
                'collected' => round((float) $monthlyInvoices->sum('paid_amount'), 2),
            ])->values();

        return view('reports.sales', [
            'from' => $from,
            'to' => $to,
            'invoiceCount' => (clone $documents)->count(),
            'revenue' => (clone $documents)->sum('total'),
            'collected' => (clone $documents)->sum('paid_amount'),
            'invoices' => $invoices,
            'topItems' => $topItems,
            'salesTrend' => $salesTrend,
        ]);
    }

    public function inventory(): View
    {
        $parts = InventoryPart::query()->withCount('movements')->orderByRaw('stock_quantity <= reorder_level DESC')->orderBy('name')->get();
        $lowStockCount = $parts->filter(fn ($part) => $part->stock_quantity <= $part->reorder_level)->count();

        return view('reports.inventory', [
            'parts' => $parts,
            'lowStockCount' => $lowStockCount,
            'stockValue' => $parts->sum(fn ($part) => (float) $part->cost_price * $part->stock_quantity),
            'recentMovements' => InventoryMovement::query()->with('part')->latest()->limit(12)->get(),
            'stockChart' => $parts->sortBy('stock_quantity')->take(10)->map(fn ($part): array => [
                'name' => $part->name,
                'quantity' => (int) $part->stock_quantity,
                'reorder' => (int) $part->reorder_level,
            ])->values(),
            'stockHealthChart' => [
                ['name' => 'At or below reorder', 'value' => $lowStockCount],
                ['name' => 'Healthy stock', 'value' => max(0, $parts->count() - $lowStockCount)],
            ],
        ]);
    }

    public function teams(): View
    {
        $users = User::query()
            ->with('teams.department')
            ->withCount('assignedTasks')
            ->withCount(['assignedTasks as completed_tasks_count' => fn ($query) => $query->where('status', 'done')])
            ->withCount(['assignedTasks as active_tasks_count' => fn ($query) => $query->where('status', 'in_progress')])
            ->orderBy('name')
            ->get();

        return view('reports.teams', [
            'users' => $users,
            'unassignedTasks' => Task::query()->whereNull('assigned_to')->count(),
            'teamChart' => $users->sortByDesc('assigned_tasks_count')->take(8)->map(fn (User $user): array => [
                'name' => $user->name,
                'assigned' => (int) $user->assigned_tasks_count,
                'completed' => (int) $user->completed_tasks_count,
                'active' => (int) $user->active_tasks_count,
            ])->values(),
        ]);
    }

    public function marketing(): View
    {
        $campaigns = MarketingCampaign::query()->orderByDesc('starts_on')->orderByDesc('created_at')->get();
        $metaConfig = config('services.meta', []);
        $whatsAppSetup = [
            'app_id' => filled($metaConfig['app_id'] ?? null),
            'app_secret' => filled($metaConfig['app_secret'] ?? null),
            'access_token' => filled($metaConfig['access_token'] ?? null),
            'business_account_id' => filled($metaConfig['whatsapp_business_account_id'] ?? null),
            'phone_number_id' => filled($metaConfig['whatsapp_phone_number_id'] ?? null),
            'webhook_verify_token' => filled($metaConfig['webhook_verify_token'] ?? null),
        ];
        $marketingSetup = [
            'app_id' => filled($metaConfig['app_id'] ?? null),
            'app_secret' => filled($metaConfig['app_secret'] ?? null),
            'access_token' => filled($metaConfig['access_token'] ?? null),
            'ad_account_id' => filled($metaConfig['ad_account_id'] ?? null),
        ];

        return view('reports.marketing', [
            'campaigns' => $campaigns,
            'spend' => $campaigns->sum('spend'),
            'revenue' => $campaigns->sum('attributed_revenue'),
            'leads' => $campaigns->sum('leads'),
            'conversions' => $campaigns->sum('conversions'),
            'whatsAppSetup' => $whatsAppSetup,
            'marketingSetup' => $marketingSetup,
            'whatsAppTemplateCount' => CustomerMessageTemplate::query()
                ->where('channel', 'whatsapp')
                ->where('is_active', true)
                ->count(),
        ]);
    }

    public function storeCampaign(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'channel' => ['required', 'string', 'max:40'],
            'status' => ['required', 'in:planned,active,completed,paused'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'spend' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'impressions' => ['required', 'integer', 'min:0'],
            'clicks' => ['required', 'integer', 'min:0'],
            'leads' => ['required', 'integer', 'min:0'],
            'conversions' => ['required', 'integer', 'min:0'],
            'attributed_revenue' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);
        MarketingCampaign::create($data);

        return back()->with('success', 'Campaign report saved.');
    }

    public function export(Request $request)
    {
        [$from, $to] = $this->period($request);
        $invoices = $this->salesDocuments()->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('issue_date')
            ->get();

        return response()->streamDownload(function () use ($invoices): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Invoice', 'Customer', 'Issued', 'Status', 'Billed', 'Paid', 'Balance']);
            foreach ($invoices as $invoice) {
                $customer = $invoice->customer_name;
                if (preg_match('/^[=+\-@]/', $customer)) {
                    $customer = "'".$customer;
                }
                fputcsv($output, [
                    $invoice->number,
                    $customer,
                    Carbon::parse($invoice->issue_date)->format('Y-m-d'),
                    $invoice->status,
                    number_format((float) $invoice->total, 2, '.', ''),
                    number_format((float) $invoice->paid_amount, 2, '.', ''),
                    number_format($invoice->balance, 2, '.', ''),
                ]);
            }
            fclose($output);
        }, 'sales-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function salesDocuments()
    {
        return BillingDocument::query()
            ->where('type', 'invoice')
            ->whereIn('status', ['issued', 'partial', 'part_paid', 'paid']);
    }

    private function period(Request $request): array
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [
            Carbon::parse($data['from'] ?? now()->startOfMonth())->startOfDay(),
            Carbon::parse($data['to'] ?? now())->endOfDay(),
        ];
    }
}
