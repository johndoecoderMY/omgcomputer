<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupDestination;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
