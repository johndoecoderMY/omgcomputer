<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        return view('projects.index', [
            'projects' => Project::query()
                ->withCount('tasks')
                ->withCount(['tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'done')])
                ->latest()
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('projects.create', ['project' => new Project()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $project = Project::create($this->validatedData($request));

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project created successfully.');
    }

    public function show(Project $project): View
    {
        $project->load([
            'tasks' => fn ($query) => $query->whereNull('parent_task_id')->with('subtasks'),
        ]);
        $project->loadCount('tasks');
        $project->loadCount([
            'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'done'),
        ]);

        return view('projects.show', compact('project'));
    }

    public function edit(Project $project): View
    {
        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $project->update($this->validatedData($request));

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project and its tasks deleted successfully.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
