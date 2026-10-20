<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Project;
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

        return redirect()->route('projects.calendar')->with('success', 'Event scheduled.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $projectId = $event->project_id;
        $event->delete();

        return $projectId
            ? redirect()->route('projects.show', $projectId)->with('success', 'Event removed.')
            : redirect()->route('projects.calendar')->with('success', 'Event removed.');
    }

    public function events(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
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
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }
        if (! $request->user()->hasRole('admin')) {
            $query->whereHas('project', fn ($projects) => $projects
                ->where(fn ($owned) => $owned->where('created_by_user_id', $request->user()->id)
                    ->orWhereHas('assignees', fn ($assignees) => $assignees->whereKey($request->user()->id))));
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
                    'project' => $event->project?->name ?? 'General event',
                    'project_url' => $event->project
                        ? route('projects.show', $event->project).'#project-schedule'
                        : null,
                    'type' => Event::TYPES[$event->event_type] ?? 'Event',
                    'location' => $event->location,
                    'description' => $event->description,
                ],
            ]);

        return response()->json($events);
    }
}
