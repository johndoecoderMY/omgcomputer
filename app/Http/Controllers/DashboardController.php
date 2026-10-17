<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\FlowChart;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('dashboard', [
            'projects' => $projects,
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
