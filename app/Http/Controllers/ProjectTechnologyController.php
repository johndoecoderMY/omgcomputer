<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTechnology;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectTechnologyController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $project->technologyStack()->create($this->validatedData($request));

        return redirect()->route('projects.show', $project)->with('success', 'Technology added to the project.');
    }

    public function update(Request $request, Project $project, ProjectTechnology $technology): RedirectResponse
    {
        $project->technologyStack()->findOrFail($technology->id)->update($this->validatedData($request));

        return redirect()->route('projects.show', $project)->with('success', 'Project technology updated.');
    }

    public function destroy(Project $project, ProjectTechnology $technology): RedirectResponse
    {
        $project->technologyStack()->findOrFail($technology->id)->delete();

        return redirect()->route('projects.show', $project)->with('success', 'Technology removed from the project.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'version' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
