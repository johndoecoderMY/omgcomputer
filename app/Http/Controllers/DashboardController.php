<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Event;
use App\Models\FlowChart;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\Task;
use App\Support\StatusBadge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasRole('customer')) {
            $email = strtolower(trim($request->user()->email));
            $projects = Project::query()
                ->visibleToCustomer($request->user())
                ->latest('updated_at')
                ->limit(5)
                ->get();
            $tickets = RepairTicket::query()
                ->whereHas('customer', fn ($customers) => $customers
                    ->whereRaw('LOWER(email) = ?', [$email]))
                ->latest('received_at');
            $bookings = Booking::query()
                ->whereRaw('LOWER(TRIM(customer_email)) = ?', [$email]);
            $upcomingBookings = (clone $bookings)
                ->where('starts_at', '>=', now())
                ->whereIn('status', ['pending', 'confirmed'])
                ->orderBy('starts_at')
                ->limit(5)
                ->get();

            return view('customer.dashboard', [
                'projects' => $projects,
                'projectCount' => Project::query()->visibleToCustomer($request->user())->count(),
                'activeTicketCount' => (clone $tickets)->whereNotIn('status', ['closed', 'cancelled'])->count(),
                'recentTickets' => (clone $tickets)->limit(5)->get(),
                'upcomingBookingCount' => (clone $bookings)
                    ->where('starts_at', '>=', now())
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->count(),
                'upcomingBookings' => $upcomingBookings,
                'unreadNotificationCount' => $request->user()->unreadNotifications()->count(),
            ]);
        }

        $projects = Project::query()
            ->withCount('tasks')
            ->withCount(['tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'done')])
            ->latest('updated_at')
            ->limit(6)
            ->get();

        $taskStatusCounts = Task::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
        $ticketStatusCounts = RepairTicket::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
        $ticketArrivals = RepairTicket::query()
            ->selectRaw('DATE(received_at) as received_date, COUNT(*) as total')
            ->whereBetween('received_at', [today()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->groupBy('received_date')
            ->pluck('total', 'received_date');

        return view('dashboard', [
            'projects' => $projects,
            'taskStatusChart' => collect(Task::STATUSES)
                ->map(fn (string $label, string $status): array => [
                    'name' => $label,
                    'value' => (int) ($taskStatusCounts[$status] ?? 0),
                    'badgeClass' => 'text-bg-'.StatusBadge::colorClass($status),
                ])->values(),
            'ticketStatusChart' => collect(RepairTicket::STATUSES)
                ->map(fn (string $label, string $status): array => [
                    'name' => $label,
                    'value' => (int) ($ticketStatusCounts[$status] ?? 0),
                    'badgeClass' => 'text-bg-'.StatusBadge::colorClass($status),
                ])->values(),
            'ticketArrivalChart' => collect(range(6, 0))
                ->map(function (int $daysAgo) use ($ticketArrivals): array {
                    $date = today()->subDays($daysAgo);
                    $key = $date->toDateString();

                    return [
                        'date' => $date->format('M j'),
                        'count' => (int) ($ticketArrivals[$key] ?? 0),
                    ];
                })->values(),
            'projectCount' => Project::query()->count(),
            'openTaskCount' => Task::query()->where('status', '!=', 'done')->count(),
            'overdueTaskCount' => Task::query()->where('status', '!=', 'done')
                ->whereNotNull('due_date')->whereDate('due_date', '<', today())->count(),
            'assignedTaskCount' => auth()->user()->assignedTasks()->where('status', '!=', 'done')->count(),
            'openTicketCount' => RepairTicket::query()->whereNotIn('status', ['closed', 'cancelled'])->count(),
            'urgentTicketCount' => RepairTicket::query()->whereNotIn('status', ['closed', 'cancelled'])
                ->whereIn('priority', ['high', 'urgent'])->count(),
            'recentTickets' => RepairTicket::query()->with(['customer', 'assignees'])
                ->whereNotIn('status', ['closed', 'cancelled'])
                ->latest('received_at')->limit(6)->get(),
            'upcomingEvents' => Event::query()->with('project:id,name')
                ->where('starts_at', '>=', now())->orderBy('starts_at')->limit(6)->get(),
            'sopExamples' => FlowChart::query()
                ->whereIn('category', array_keys(FlowChart::CATEGORIES))
                ->latest('updated_at')->limit(6)->get(),
            'unreadNotificationCount' => auth()->user()->unreadNotifications()->count(),
        ]);
    }
}
