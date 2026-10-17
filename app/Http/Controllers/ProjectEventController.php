<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\WorkspaceNotification;
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

        return view('projects.calendar', [
            'monthStart' => $monthStart,
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
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

        $event = Event::query()->create($data);
        if ($event->project_id) {
            $assignees = User::query()
                ->whereIn('id', Task::query()
                    ->where('project_id', $event->project_id)
                    ->whereNotNull('assigned_to')
                    ->select('assigned_to'))
                ->get();
            $projectName = Project::query()->whereKey($event->project_id)->value('name');

            foreach ($assignees as $assignee) {
                if ((int) $assignee->id === (int) auth()->id()) {
                    continue;
                }

                $assignee->notify(new WorkspaceNotification(
                    'Project event scheduled',
                    sprintf('“%s” was scheduled for %s on %s.', $event->title, $projectName, $event->starts_at->format('M j, Y g:i A')),
                    route('projects.show', $event->project_id),
                ));
            }
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

        $events = $query->get()
            ->map(fn (Event $event): array => [
                'id' => $event->id,
                'title' => $event->title,
                'start' => $event->starts_at->toIso8601String(),
                'end' => $event->ends_at?->toIso8601String(),
                'url' => $event->project
                    ? route('projects.show', $event->project).'#project-schedule'
                    : null,
                'backgroundColor' => Event::COLORS[$event->event_type] ?? Event::COLORS['other'],
                'borderColor' => Event::COLORS[$event->event_type] ?? Event::COLORS['other'],
                'extendedProps' => [
                    'project' => $event->project?->name ?? 'General event',
                    'type' => Event::TYPES[$event->event_type] ?? 'Event',
                    'location' => $event->location,
                    'description' => $event->description,
                ],
            ]);

        return response()->json($events);
    }
}
