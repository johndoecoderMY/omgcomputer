<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use ZipArchive;

class DatabaseTableExportService
{
    /**
     * @param  list<string>  $tables
     */
    public function createArchive(array $tables): string
    {
        $archivePath = tempnam(sys_get_temp_dir(), 'database-export-');
        if ($archivePath === false) {
            throw new RuntimeException('A temporary export archive could not be created.');
        }

        $zip = new ZipArchive;
        if ($zip->open($archivePath, ZipArchive::OVERWRITE) !== true) {
            unlink($archivePath);
            throw new RuntimeException('The temporary export archive could not be opened.');
        }

        $manifest = [
            'format' => 'CSV table data export',
            'generated_at' => now()->toIso8601String(),
            'database_connection' => config('database.default'),
            'tables' => [],
        ];
        $temporaryCsvFiles = [];

        try {
            foreach ($tables as $table) {
                $columns = Schema::getColumnListing($table);
                $csvPath = tempnam(sys_get_temp_dir(), 'database-table-');
                if ($csvPath === false) {
                    throw new RuntimeException("A temporary CSV file could not be created for [{$table}].");
                }
                $temporaryCsvFiles[] = $csvPath;

                $stream = fopen($csvPath, 'wb');
                if ($stream === false) {
                    throw new RuntimeException("The temporary CSV file for [{$table}] could not be opened.");
                }

                try {
                    fputcsv($stream, $columns);
                    foreach (DB::table($table)->cursor() as $row) {
                        fputcsv($stream, array_values((array) $row));
                    }
                } finally {
                    fclose($stream);
                }

                if (! $zip->addFile($csvPath, 'tables/'.$table.'.csv')) {
                    throw new RuntimeException("The CSV export for [{$table}] could not be added to the archive.");
                }

                $manifest['tables'][$table] = [
                    'columns' => $columns,
                    'format' => 'CSV; the first row contains column names.',
                    'null_value' => 'Empty values are exported as empty CSV fields.',
                ];
            }

            if (! $zip->addFromString(
                'manifest.json',
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            )) {
                throw new RuntimeException('The export manifest could not be added to the archive.');
            }

            if (! $zip->close()) {
                throw new RuntimeException('The selected table export archive could not be finalized.');
            }
        } catch (\Throwable $exception) {
            $zip->close();
            if (is_file($archivePath)) {
                unlink($archivePath);
            }
            throw $exception;
        } finally {
            foreach ($temporaryCsvFiles as $csvPath) {
                if (is_file($csvPath)) {
                    unlink($csvPath);
                }
            }
        }

        return $archivePath;
    }
}
