<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectLogoController extends Controller
{
    private const MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function show(Request $request, Project $project): BinaryFileResponse
    {
        if ($request->user()->hasRole('customer')) {
            abort_unless(
                Project::query()->visibleToCustomer($request->user())->whereKey($project->id)->exists(),
                404,
            );
        }

        $path = $project->logo_path;
        abort_unless(
            $this->isManagedPath($path) && Storage::disk('local')->exists($path),
            404,
        );

        $mimeType = Storage::disk('local')->mimeType($path);
        abort_unless(in_array($mimeType, self::MIME_TYPES, true), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $path = $data['logo']->store('project-logos', 'local');
        if (! is_string($path) || $path === '') {
            throw ValidationException::withMessages([
                'logo' => 'The server could not save the project logo. Check storage permissions.',
            ]);
        }

        $previousPath = $project->logo_path;
        $project->update(['logo_path' => $path]);
        $project->activities()->create([
            'user_id' => $request->user()->id,
            'summary' => 'Updated project logo',
            'details' => [$previousPath ? 'Logo replaced' : 'Logo added'],
        ]);

        if ($previousPath && $previousPath !== $path && ! $this->deleteStoredLogo($previousPath)) {
            Log::warning('A replaced project logo file could not be removed.', [
                'project_id' => $project->id,
            ]);

            return redirect()->route('projects.show', $project)
                ->with('warning', 'The logo was updated, but the previous image could not be removed. Contact an administrator.');
        }

        return redirect()->route('projects.show', $project)->with('success', 'Project logo updated.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $path = $project->logo_path;
        if (! $path) {
            return redirect()->route('projects.show', $project)
                ->with('error', 'This project does not have a logo to remove.');
        }

        $project->update(['logo_path' => null]);
        $project->activities()->create([
            'user_id' => $request->user()->id,
            'summary' => 'Updated project logo',
            'details' => ['Logo removed'],
        ]);

        if (! $this->deleteStoredLogo($path)) {
            Log::warning('A removed project logo file could not be deleted.', [
                'project_id' => $project->id,
            ]);

            return redirect()->route('projects.show', $project)
                ->with('warning', 'The logo was removed from the project, but its file could not be deleted. Contact an administrator.');
        }

        return redirect()->route('projects.show', $project)->with('success', 'Project logo removed.');
    }

    private function deleteStoredLogo(string $path): bool
    {
        if (! $this->isManagedPath($path)) {
            return false;
        }

        return ! Storage::disk('local')->exists($path)
            || Storage::disk('local')->delete($path);
    }

    private function isManagedPath(?string $path): bool
    {
        return $path !== null
            && str_starts_with($path, 'project-logos/')
            && ! str_contains($path, '..');
    }
}
