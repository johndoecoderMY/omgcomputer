<?php

namespace App\Support\Backup;

use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupCollection;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;

class KeepLastTenStrategy extends DefaultStrategy
{
    public function deleteOldBackups(BackupCollection $backups): void
    {
        parent::deleteOldBackups($backups);

        if ($this->newestBackup === null) {
            return;
        }

        $backups
            ->prepend($this->newestBackup)
            ->filter(fn (Backup $backup) => $backup->exists())
            ->slice(10)
            ->each(fn (Backup $backup) => $backup->delete());
    }
}
