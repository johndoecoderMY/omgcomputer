<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Project;
use App\Models\Task;
use App\Services\WorkspaceNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectEventController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $monthStart = CarbonImmutable::parse(($data['month'] ?? now()->format('Y-m')).'-01')->startOfMonth();

        $projects = Project::query()->orderBy('name');
        if (! $request->user()->hasRole('admin')) {
            $projects->where(fn ($query) => $query
                ->where('created_by_user_id', $request->user()->id)
                ->orWhereHas('assignees', fn ($assignees) => $assignees->whereKey($request->user()->id)));
        }

        return view('projects.calendar', [
            'monthStart' => $monthStart,
            'projects' => $projects->get(['id', 'name']),
            'selectedProject' => null,
        ]);
    }

    public function projectIndex(Request $request, Project $project): View
    {
        $data = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $monthStart = CarbonImmutable::parse(($data['month'] ?? now()->format('Y-m')).'-01')->startOfMonth();
        $projects = Project::query()->orderBy('name');
        if (! $request->user()->hasRole('admin')) {
            $projects->where(fn ($query) => $query
                ->where('created_by_user_id', $request->user()->id)
                ->orWhereHas('assignees', fn ($assignees) => $assignees->whereKey($request->user()->id)));
            abort_unless((clone $projects)->whereKey($project->id)->exists(), 404);
        }

        return view('projects.calendar', [
            'monthStart' => $monthStart,
            'projects' => $projects->get(['id', 'name']),
            'selectedProject' => $project,
        ]);
    }

    public function store(Request $request, WorkspaceNotifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:180'],
            'event_type' => ['required', 'string', Rule::in(array_keys(Event::TYPES))],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'location' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'return_to' => ['nullable', 'string', Rule::in(['calendar', 'project'])],
        ]);
        $returnTo = $data['return_to'] ?? 'calendar';
        unset($data['return_to']);
        if (! $request->user()->hasRole('admin')) {
            abort_unless($data['project_id'] ?? null, 403);
        }

        $event = Event::query()->create($data);
        if ($event->project_id) {
            $project = Project::query()
                ->with(['creator', 'assignees', 'tasks.assignees'])
                ->findOrFail($event->project_id);
            $recipients = collect([$project->creator])
                ->merge($project->assignees)
                ->merge($project->tasks->flatMap(fn ($task) => $task->assignees));
            $notifier->notifyUsers(
                $recipients,
                $request->user(),
                'Project event scheduled',
                sprintf('“%s” was scheduled for %s on %s.', $event->title, $project->name, $event->starts_at->format('M j, Y g:i A')),
                route('projects.show', $event->project_id),
            );
        }

        if ($returnTo === 'project' && ! empty($data['project_id'])) {
            return redirect()->route('projects.show', $data['project_id'])->with('success', 'Event scheduled.');
        }

        return redirect()->route('calendar')->with('success', 'Event scheduled.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $projectId = $event->project_id;
        $event->delete();

        return $projectId
            ? redirect()->route('projects.show', $projectId)->with('success', 'Event removed.')
            : redirect()->route('calendar')->with('success', 'Event removed.');
    }

    public function events(Request $request): JsonResponse
    {
        return $this->calendarEvents($request);
    }

    public function projectEvents(Request $request, Project $project): JsonResponse
    {
        if (! $request->user()->hasRole('admin')) {
            abort_unless(
                (int) $project->created_by_user_id === (int) $request->user()->id
                    || $project->assignees()->whereKey($request->user()->id)->exists(),
                403,
            );
        }

        return $this->calendarEvents($request, $project);
    }

    private function calendarEvents(Request $request, ?Project $project = null): JsonResponse
    {
        $data = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);
        $start = CarbonImmutable::parse($data['start']);
        $end = CarbonImmutable::parse($data['end']);

        $query = Event::query()
            ->with('project:id,name')
            ->where('starts_at', '<', $end)
            ->where(function ($query) use ($start): void {
                $query->where('ends_at', '>=', $start)
                    ->orWhere(function ($query) use ($start): void {
                        $query->whereNull('ends_at')
                            ->where('starts_at', '>=', $start);
                    });
            })
            ->orderBy('starts_at');
        if ($project) {
            $query->where('project_id', $project->id);
        }
        if (! $request->user()->hasRole('admin')) {
            $query->where(fn ($events) => $events
                ->whereNull('project_id')
                ->orWhereHas('project', fn ($projects) => $projects
                    ->where(fn ($owned) => $owned->where('created_by_user_id', $request->user()->id)
                        ->orWhereHas('assignees', fn ($assignees) => $assignees->whereKey($request->user()->id)))));
        }

        $events = $query->get()
            ->map(fn (Event $event): array => [
                'id' => $event->id,
                'title' => $event->title,
                'start' => $event->starts_at->toIso8601String(),
                'end' => $event->ends_at?->toIso8601String(),
                'url' => $event->project
                    ? route('projects.show', $event->project).'#project-schedule'
                    : null,
                'backgroundColor' => Event::BACKGROUND_COLORS[$event->event_type] ?? Event::BACKGROUND_COLORS['other'],
                'borderColor' => Event::BACKGROUND_COLORS[$event->event_type] ?? Event::BACKGROUND_COLORS['other'],
                'textColor' => Event::COLORS[$event->event_type] ?? Event::COLORS['other'],
                'extendedProps' => [
                    'kind' => 'event',
                    'project' => $event->project?->name ?? 'General event',
                    'project_url' => $event->project
                        ? route('projects.show', $event->project).'#project-schedule'
                        : null,
                    'type' => Event::TYPES[$event->event_type] ?? 'Event',
                    'location' => $event->location,
                    'description' => $event->description,
                ],
            ]);

        if (! $project) {
            $bookings = Booking::query()
                ->with('customer')
                ->whereIn('status', ['pending', 'confirmed', 'completed'])
                ->where('starts_at', '<', $end)
                ->where('starts_at', '>=', $start->copy()->subDays(46))
                ->orderBy('starts_at')
                ->get()
                ->filter(fn (Booking $booking): bool => $booking->starts_at->copy()
                    ->addMinutes($booking->duration_minutes)
                    ->gt($start))
                ->map(fn (Booking $booking): array => [
                    'id' => 'booking-'.$booking->id,
                    'title' => $booking->customer_name.' · '.$booking->service_name,
                    'start' => $booking->starts_at->toIso8601String(),
                    'end' => $booking->starts_at->copy()->addMinutes($booking->duration_minutes)->toIso8601String(),
                    'backgroundColor' => Booking::CALENDAR_COLORS[$booking->status]['background'] ?? Booking::CALENDAR_COLORS['pending']['background'],
                    'borderColor' => Booking::CALENDAR_COLORS[$booking->status]['background'] ?? Booking::CALENDAR_COLORS['pending']['background'],
                    'textColor' => Booking::CALENDAR_COLORS[$booking->status]['text'] ?? Booking::CALENDAR_COLORS['pending']['text'],
                    'extendedProps' => [
                        'kind' => 'booking',
                        'project' => 'Booking',
                        'reference' => $booking->reference,
                        'customer' => $booking->customer_name,
                        'service' => $booking->service_name,
                        'type' => 'Customer booking',
                        'status' => Booking::STATUSES[$booking->status] ?? $booking->status,
                        'location' => $booking->customer_phone,
                        'description' => $booking->request_notes,
                        'project_url' => null,
                        'customer_url' => $booking->customer
                            ? route('customers.show', $booking->customer)
                            : null,
                        'booking_url' => route('bookings.show', $booking),
                    ],
                ]);

            $events = $events->concat($bookings)->sortBy('start')->values();
        }

        $tasksQuery = Task::query()
            ->with('project:id,name')
            ->whereNull('parent_task_id')
            ->where(function ($tasks) use ($start, $end): void {
                $tasks->where(fn ($due) => $due
                    ->whereNotNull('due_date')
                    ->where('due_date', '>=', $start->toDateString())
                    ->where('due_date', '<', $end->toDateString()))
                    ->orWhere(fn ($scheduled) => $scheduled
                        ->whereNotNull('start_date')
                        ->where('start_date', '>=', $start->toDateString())
                        ->where('start_date', '<', $end->toDateString()))
                    ->orWhere(fn ($ongoing) => $ongoing
                        ->whereNotNull('start_date')
                        ->whereNotNull('due_date')
                        ->where('start_date', '<', $start->toDateString())
                        ->where('due_date', '>=', $start->toDateString()));
            });
        if ($project) {
            $tasksQuery->where('project_id', $project->id);
        } elseif (! $request->user()->hasRole('admin')) {
            $tasksQuery->where(fn ($visible) => $visible
                ->where(function ($personal) use ($request): void {
                    $personal->whereNull('project_id')
                        ->where(fn ($owned) => $owned
                            ->where('created_by_user_id', $request->user()->id)
                            ->orWhere('assigned_to', $request->user()->id)
                            ->orWhereHas('assignees', fn ($assignees) => $assignees->whereKey($request->user()->id)));
                })
                ->orWhereHas('project', fn ($projects) => $projects
                    ->where(fn ($owned) => $owned->where('created_by_user_id', $request->user()->id)
                        ->orWhereHas('assignees', fn ($assignees) => $assignees->whereKey($request->user()->id)))));
        }

        $taskEvents = $tasksQuery->orderBy('start_date')->orderBy('due_date')->get()
            ->map(fn (Task $task): array => [
                'id' => 'task-'.$task->id,
                'title' => $task->title,
                'start' => ($task->start_date ?? $task->due_date)->toDateString(),
                'end' => ($task->due_date ?? $task->start_date)->copy()->addDay()->toDateString(),
                'allDay' => true,
                'backgroundColor' => Event::BACKGROUND_COLORS[$task->priority === 'urgent' ? 'debugging' : ($task->priority === 'high' ? 'progress_review' : 'consultation')],
                'borderColor' => Event::BACKGROUND_COLORS[$task->priority === 'urgent' ? 'debugging' : ($task->priority === 'high' ? 'progress_review' : 'consultation')],
                'textColor' => Event::COLORS[$task->priority === 'urgent' ? 'debugging' : ($task->priority === 'high' ? 'progress_review' : 'consultation')],
                'extendedProps' => [
                    'kind' => 'task',
                    'project' => $task->project?->name ?? 'Task',
                    'task_title' => $task->title,
                    'task_url' => route('tasks.show', $task),
                    'type' => 'Task · '.ucfirst($task->priority).' priority',
                    'location' => null,
                    'description' => $task->description,
                    'status' => Task::STATUSES[$task->status] ?? $task->status,
                    'project_url' => $task->project ? route('projects.show', $task->project) : null,
                    'reference' => null,
                ],
            ]);
        $events = $events->concat($taskEvents)->sortBy('start')->values();

        return response()->json($events);
    }
}
