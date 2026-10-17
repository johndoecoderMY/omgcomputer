<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\FlowChart;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasRole('customer')) {
            return redirect()->route('projects.index');
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
                ])->values(),
            'ticketStatusChart' => collect(RepairTicket::STATUSES)
                ->map(fn (string $label, string $status): array => [
                    'name' => $label,
                    'value' => (int) ($ticketStatusCounts[$status] ?? 0),
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
            'assignedTaskCount' => Task::query()->where('assigned_to', auth()->id())->where('status', '!=', 'done')->count(),
            'openTicketCount' => RepairTicket::query()->whereNotIn('status', ['closed', 'cancelled'])->count(),
            'urgentTicketCount' => RepairTicket::query()->whereNotIn('status', ['closed', 'cancelled'])
                ->whereIn('priority', ['high', 'urgent'])->count(),
            'recentTickets' => RepairTicket::query()->with(['customer', 'assignee'])
                ->whereNotIn('status', ['closed', 'cancelled'])
                ->latest('received_at')->limit(6)->get(),
            'upcomingEvents' => Event::query()->with('project:id,name')
                ->where('starts_at', '>=', now())->orderBy('starts_at')->limit(6)->get(),
            'sopExamples' => FlowChart::query()
                ->whereIn('category', ['website', 'process', 'education', 'service', 'product'])
                ->latest('updated_at')->limit(6)->get(),
            'unreadNotificationCount' => auth()->user()->unreadNotifications()->count(),
        ]);
    }
}
