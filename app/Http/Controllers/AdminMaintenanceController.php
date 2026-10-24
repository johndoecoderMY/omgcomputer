<?php

namespace App\Http\Controllers;

use App\Services\GitPullService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
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

    public function optimizeClear(): RedirectResponse
    {
        return $this->runCommand(
            'optimize:clear',
            [],
            'Laravel optimization caches cleared.',
        );
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
