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
use Illuminate\Support\Facades\DB;
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
            'trackingApiConfigured' => filled(config('services.parcel_tracking.api_key')),
        ]);
    }

    public function sales(Request $request): View
    {
        [$from, $to] = $this->period($request);
        $documents = $this->salesDocuments()->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()]);
        $invoices = (clone $documents)->latest('issue_date')->paginate(50)->withQueryString();
        $topItems = BillingDocumentItem::query()
            ->selectRaw('billing_document_items.description, SUM(billing_document_items.quantity) as units, SUM(billing_document_items.line_total) as revenue')
            ->whereHas('document', fn ($query) => $query->where('type', 'invoice')
                ->whereIn('status', ['issued', 'part_paid', 'paid'])
                ->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()]))
            ->groupBy('billing_document_items.description')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();
        $monthExpression = match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => "DATE_FORMAT(issue_date, '%Y-%m')",
            'pgsql' => "TO_CHAR(issue_date, 'YYYY-MM')",
            'sqlsrv' => "FORMAT(issue_date, 'yyyy-MM')",
            default => "strftime('%Y-%m', issue_date)",
        };
        $salesTrend = (clone $documents)
            ->selectRaw("{$monthExpression} as month, SUM(total) as revenue, SUM(paid_amount) as collected")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row): array => [
                'month' => Carbon::createFromFormat('Y-m', $row->month)->format('M Y'),
                'revenue' => round((float) $row->revenue, 2),
                'collected' => round((float) $row->collected, 2),
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
        $parts = InventoryPart::query()
            ->withCount('movements')
            ->orderByRaw('stock_quantity <= reorder_level DESC')
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();
        $partCount = InventoryPart::query()->count();
        $lowStockCount = InventoryPart::query()->whereColumn('stock_quantity', '<=', 'reorder_level')->count();
        $healthyStockCount = max(0, $partCount - $lowStockCount);

        return view('reports.inventory', [
            'parts' => $parts,
            'partCount' => $partCount,
            'lowStockCount' => $lowStockCount,
            'stockValue' => (float) InventoryPart::query()->sum(DB::raw('cost_price * stock_quantity')),
            'recentMovements' => InventoryMovement::query()->with('part')->latest()->limit(12)->get(),
            'stockChart' => InventoryPart::query()
                ->orderBy('stock_quantity')
                ->limit(10)
                ->get(['name', 'stock_quantity', 'reorder_level'])
                ->map(fn (InventoryPart $part): array => [
                    'name' => $part->name,
                    'quantity' => (int) $part->stock_quantity,
                    'reorder' => (int) $part->reorder_level,
                ])->values(),
            'stockHealthChart' => [
                ['name' => 'At or below reorder', 'value' => $lowStockCount],
                ['name' => 'Healthy stock', 'value' => $healthyStockCount],
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
            ->paginate(50)
            ->withQueryString();
        $assignedTasks = DB::table('user_assignments')
            ->where('assignable_type', Task::class);
        $completedTasks = DB::table('user_assignments')
            ->join('tasks', 'tasks.id', '=', 'user_assignments.assignable_id')
            ->where('user_assignments.assignable_type', Task::class)
            ->where('tasks.status', 'done');

        return view('reports.teams', [
            'users' => $users,
            'memberCount' => User::query()->count(),
            'assignedTaskCount' => (clone $assignedTasks)->count(),
            'completedTaskCount' => (clone $completedTasks)->count(),
            'unassignedTasks' => Task::query()->whereDoesntHave('assignees')->count(),
            'teamChart' => User::query()
                ->select(['users.id', 'users.name'])
                ->withCount('assignedTasks')
                ->withCount(['assignedTasks as completed_tasks_count' => fn ($query) => $query->where('status', 'done')])
                ->withCount(['assignedTasks as active_tasks_count' => fn ($query) => $query->where('status', 'in_progress')])
                ->orderByDesc('assigned_tasks_count')
                ->limit(8)
                ->get()
                ->map(fn (User $user): array => [
                    'name' => $user->name,
                    'assigned' => (int) $user->assigned_tasks_count,
                    'completed' => (int) $user->completed_tasks_count,
                    'active' => (int) $user->active_tasks_count,
                ])->values(),
        ]);
    }

    public function marketing(): View
    {
        $campaigns = MarketingCampaign::query()
            ->orderByDesc('starts_on')
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();
        $campaignTotals = MarketingCampaign::query()
            ->selectRaw('SUM(spend) as spend, SUM(attributed_revenue) as revenue, SUM(leads) as leads, SUM(conversions) as conversions')
            ->first();
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
            'spend' => (float) $campaignTotals->spend,
            'revenue' => (float) $campaignTotals->revenue,
            'leads' => (int) $campaignTotals->leads,
            'conversions' => (int) $campaignTotals->conversions,
            'whatsAppSetup' => $whatsAppSetup,
            'marketingSetup' => $marketingSetup,
            'whatsAppTemplateCount' => CustomerMessageTemplate::query()
                ->where('channel', 'whatsapp')
                ->where('is_active', true)
                ->count(),
        ]);
    }

    public function tiktok(): View
    {
        $config = config('services.tiktok', []);

        return view('marketing.tiktok', [
            'setup' => [
                'client_key' => filled($config['client_key'] ?? null),
                'client_secret' => filled($config['client_secret'] ?? null),
                'redirect_uri' => filled($config['redirect_uri'] ?? null),
            ],
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
            ->cursor();

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
