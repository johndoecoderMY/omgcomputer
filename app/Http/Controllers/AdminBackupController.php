<?php

namespace App\Http\Controllers;

use App\Services\DatabaseTableExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupDestination;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AdminBackupController extends Controller
{
    public function index(): View
    {
        $backupName = config('backup.backup.name');
        $destinations = collect(config('backup.backup.destination.disks', []))
            ->map(function (string $diskName) use ($backupName): array {
                $destination = BackupDestination::create($diskName, $backupName);
                $reachable = $destination->isReachable();
                $backups = $reachable ? $destination->backups() : collect();
                $newestBackup = $backups->first();

                return [
                    'disk' => $diskName,
                    'reachable' => $reachable,
                    'backups' => $backups,
                    'newest' => $newestBackup,
                    'total_size' => $backups->sum(fn (Backup $backup): float => $backup->sizeInBytes()),
                ];
            });

        return view('admin.backups.index', [
            'backupName' => $backupName,
            'destinations' => $destinations,
            'databaseTables' => collect(Schema::getTables())
                ->pluck('name')
                ->filter(fn (mixed $name): bool => is_string($name) && $name !== '')
                ->sort()
                ->values(),
        ]);
    }

    public function run(): RedirectResponse
    {
        $exitCode = Artisan::call('backup:run');

        if ($exitCode !== 0) {
            return redirect()->route('admin.backups.index')
                ->with('error', trim(Artisan::output()) ?: 'The backup command failed. Check the application logs for details.');
        }

        return redirect()->route('admin.backups.index')
            ->with('success', trim(Artisan::output()) ?: 'Backup completed successfully.');
    }

    public function exportTables(Request $request, DatabaseTableExportService $exporter): StreamedResponse|RedirectResponse
    {
        $tableNames = collect(Schema::getTables())
            ->pluck('name')
            ->filter(fn (mixed $name): bool => is_string($name) && $name !== '')
            ->values()
            ->all();

        $validated = $request->validate([
            'tables' => ['required', 'array', 'min:1'],
            'tables.*' => ['required', 'string', 'distinct', Rule::in($tableNames)],
        ]);

        try {
            $archivePath = $exporter->createArchive($validated['tables']);
        } catch (Throwable $exception) {
            Log::error('Selected database table export failed.', [
                'tables' => $validated['tables'],
                'exception' => $exception->getMessage(),
            ]);

            return redirect()->route('admin.backups.index')
                ->with('error', 'The selected table export could not be completed. Check the application logs and try again.');
        }

        $filename = 'database-tables-'.now()->format('Y-m-d-His').'.zip';

        return response()->streamDownload(function () use ($archivePath): void {
            try {
                $stream = fopen($archivePath, 'rb');
                if ($stream === false) {
                    throw new \RuntimeException('The generated table export archive could not be opened.');
                }

                fpassthru($stream);
                fclose($stream);
            } finally {
                if (is_file($archivePath)) {
                    unlink($archivePath);
                }
            }
        }, $filename, [
            'Content-Type' => 'application/zip',
        ]);
    }

    public function download(string $disk, string $backup): StreamedResponse
    {
        abort_unless(in_array($disk, config('backup.backup.destination.disks', []), true), 404);
        abort_unless(basename($backup) === $backup, 404);

        $destination = BackupDestination::create($disk, config('backup.backup.name'));
        abort_unless($destination->isReachable(), 404);

        $archive = $destination->backups()
            ->first(fn (Backup $candidate): bool => basename($candidate->path()) === $backup);
        abort_unless($archive instanceof Backup && $archive->exists(), 404);

        return response()->streamDownload(function () use ($archive): void {
            $stream = $archive->stream();
            fpassthru($stream);
            fclose($stream);
        }, $backup, [
            'Content-Type' => 'application/zip',
            'Content-Length' => (string) $archive->sizeInBytes(),
        ]);
    }
}
