<?php

namespace Tests\Feature;

use App\Support\Backup\KeepLastTenStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class BackupRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleanup_keeps_only_the_ten_newest_archives(): void
    {
        Storage::fake('backup_test');
        config([
            'backup.backup.name' => 'backup-test-app',
            'backup.backup.destination.disks' => ['backup_test'],
            'backup.cleanup.strategy' => KeepLastTenStrategy::class,
        ]);
        \Spatie\Backup\Config\Config::rebind();

        $temporaryArchive = tempnam(sys_get_temp_dir(), 'backup-retention-test-');
        $this->assertNotFalse($temporaryArchive);

        $zip = new ZipArchive;
        $this->assertSame(true, $zip->open($temporaryArchive, ZipArchive::CREATE));
        $zip->addFromString('backup.txt', 'test archive');
        $zip->close();
        $archiveContents = file_get_contents($temporaryArchive);
        unlink($temporaryArchive);

        $baseDate = now()->startOfDay();
        for ($index = 0; $index < 12; $index++) {
            $filename = $baseDate->copy()->addSeconds($index)->format('Y-m-d-H-i-s').'.zip';
            Storage::disk('backup_test')->put("backup-test-app/{$filename}", $archiveContents);
        }

        $this->assertSame(0, Artisan::call('backup:clean'), Artisan::output());

        $remainingArchives = Storage::disk('backup_test')->files('backup-test-app');
        sort($remainingArchives);

        $this->assertCount(10, $remainingArchives);
        $this->assertSame(
            $baseDate->copy()->addSeconds(2)->format('Y-m-d-H-i-s').'.zip',
            basename($remainingArchives[0]),
        );
        $this->assertSame(
            $baseDate->copy()->addSeconds(11)->format('Y-m-d-H-i-s').'.zip',
            basename($remainingArchives[9]),
        );
    }
}
