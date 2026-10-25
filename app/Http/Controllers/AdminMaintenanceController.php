<?php

namespace App\Http\Controllers;

use App\Services\GitPullService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class AdminMaintenanceController extends Controller
{
    public function index(): View
    {
        return view('admin.maintenance.index');
    }

    public function migrate(): RedirectResponse
    {
        return $this->runCommand(
            'migrate',
            ['--force' => true],
            'Database migrations completed.',
        );
    }

    public function freshSeed(Request $request): RedirectResponse
    {
        $request->validate([
            'confirmation' => ['required', 'in:RESET AND SEED DATABASE'],
        ]);

        return $this->runCommand(
            'migrate:fresh',
            ['--force' => true, '--seed' => true],
            'Database tables were recreated and seeders completed.',
        );
    }

    public function optimizeClear(): RedirectResponse
    {
        return $this->runCommand(
            'optimize:clear',
            [],
            'Laravel optimization caches cleared.',
        );
    }

    public function storageLink(): RedirectResponse
    {
        $link = public_path('storage');
        $target = storage_path('app/public');
        $resolvedTarget = realpath($target);

        if ($resolvedTarget !== false && realpath($link) === $resolvedTarget) {
            return redirect()->route('admin.maintenance.index')
                ->with('maintenance_success', 'The public storage link is already configured.');
        }

        if (file_exists($link) || is_link($link)) {
            Log::warning('Admin storage link was not created because the public path is occupied.', [
                'link' => $link,
                'target' => $target,
            ]);

            return redirect()->route('admin.maintenance.index')
                ->with('maintenance_error', 'The public/storage path already exists but does not point to the application storage directory. Resolve it manually before retrying.');
        }

        try {
            $exitCode = Artisan::call('storage:link');
            $output = trim(Artisan::output());
        } catch (RuntimeException $exception) {
            Log::error('Admin storage link command failed.', [
                'reason' => Str::limit($exception->getMessage(), 200),
            ]);

            return redirect()->route('admin.maintenance.index')
                ->with('maintenance_error', 'The storage link could not be created. Check filesystem permissions and application logs.');
        }

        if ($exitCode !== 0 || $resolvedTarget === false || realpath($link) !== $resolvedTarget) {
            Log::error('Admin storage link command did not create the expected link.', [
                'exit_code' => $exitCode,
                'output' => $output,
                'link' => $link,
                'target' => $target,
            ]);

            return redirect()->route('admin.maintenance.index')
                ->with('maintenance_error', 'The storage link could not be verified after creation. Check filesystem permissions and application logs.')
                ->with('maintenance_output', $output);
        }

        return redirect()->route('admin.maintenance.index')
            ->with('maintenance_success', 'The public storage link was created successfully.')
            ->with('maintenance_output', $output);
    }

    public function buildAssets(): RedirectResponse
    {
        try {
            $result = Process::path(base_path())->timeout(180)->run(['npm', 'run', 'build']);
        } catch (RuntimeException $exception) {
            Log::warning('Admin frontend asset build could not start.', [
                'reason' => Str::limit($exception->getMessage(), 200),
            ]);

            return redirect()->route('admin.maintenance.index')
                ->with('maintenance_error', 'The frontend build could not start. Confirm Node.js, npm, and the project dependencies are available on the server.');
        }

        $output = trim($result->output()."\n".$result->errorOutput());

        if (! $result->successful()) {
            Log::error('Admin frontend asset build failed.', [
                'exit_code' => $result->exitCode(),
                'output' => $output,
            ]);

            return redirect()->route('admin.maintenance.index')
                ->with('maintenance_error', 'The frontend asset build failed. Review the output and confirm Node.js, npm, and the project dependencies are available on the server.')
                ->with('maintenance_output', $output);
        }

        return redirect()->route('admin.maintenance.index')
            ->with('maintenance_success', 'Frontend assets built successfully.')
            ->with('maintenance_output', $output);
    }

    public function gitPull(GitPullService $git): RedirectResponse
    {
        try {
            $git->pull();
        } catch (RuntimeException $exception) {
            Log::warning('Admin Git pull could not be completed.', [
                'reason' => Str::limit($exception->getMessage(), 200),
            ]);

            return redirect()->route('admin.maintenance.index')
                ->with('maintenance_error', $exception->getMessage());
        }

        return redirect()->route('admin.maintenance.index')
            ->with('maintenance_success', 'Git pull completed. Review the site, then run migrations or clear caches if needed.');
    }

    private function runCommand(string $command, array $arguments, string $successMessage): RedirectResponse
    {
        try {
            $exitCode = Artisan::call($command, $arguments);
            $output = trim(Artisan::output());
        } catch (QueryException $exception) {
            Log::warning('Admin maintenance command could not access the database.', [
                'command' => $command,
                'exception' => $exception->getMessage(),
            ]);

            return redirect()->route('admin.maintenance.index')
                ->with('maintenance_error', 'The command could not access the database. Check the database connection and user privileges, then try again.');
        }

        if ($exitCode !== 0) {
            Log::error('Admin maintenance command failed.', [
                'command' => $command,
                'exit_code' => $exitCode,
                'output' => $output,
            ]);

            return redirect()->route('admin.maintenance.index')
                ->with('maintenance_error', 'The command failed. Review its output below and check the application logs.')
                ->with('maintenance_output', $output);
        }

        return redirect()->route('admin.maintenance.index')
            ->with('maintenance_success', $successMessage)
            ->with('maintenance_output', $output);
    }
}
