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

    /**
     * @param  list<string>  $tables
     */
    public function createSqlFile(array $tables, bool $createTables, bool $emptyTablesFirst): string
    {
        $path = tempnam(sys_get_temp_dir(), 'database-export-');
        if ($path === false) {
            throw new RuntimeException('A temporary SQL export file could not be created.');
        }

        $stream = fopen($path, 'wb');
        if ($stream === false) {
            unlink($path);
            throw new RuntimeException('The temporary SQL export file could not be opened.');
        }

        try {
            $this->writeSql($stream, "-- Database table export generated ".now()->toIso8601String().PHP_EOL);
            $this->writeSql($stream, "-- Review this file before importing it. It does not modify the current database.".PHP_EOL.PHP_EOL);

            if ($createTables) {
                foreach ($tables as $table) {
                    $statement = rtrim($this->createTableStatement($table), " \t\n\r\0\x0B;");
                    $this->writeSql($stream, $statement.';'.PHP_EOL.PHP_EOL);
                }
            }

            if ($emptyTablesFirst) {
                $this->writeSql($stream, "-- Existing rows are deleted before any exported rows are inserted.".PHP_EOL);
                foreach ($tables as $table) {
                    $this->writeSql($stream, 'DELETE FROM '.$this->wrapTable($table).';'.PHP_EOL);
                }
                $this->writeSql($stream, PHP_EOL);
            }

            $connection = DB::connection();
            $grammar = $connection->getQueryGrammar();

            foreach ($tables as $table) {
                $columns = Schema::getColumnListing($table);
                $wrappedColumns = implode(', ', array_map(
                    fn (string $column): string => $grammar->wrap($column),
                    $columns,
                ));

                foreach (DB::table($table)->cursor() as $row) {
                    $values = array_map(
                        fn (mixed $value): string => $this->sqlValue($value),
                        array_values((array) $row),
                    );
                    $this->writeSql(
                        $stream,
                        'INSERT INTO '.$this->wrapTable($table).' ('.$wrappedColumns.') VALUES ('
                            .implode(', ', $values).');'.PHP_EOL,
                    );
                }
                $this->writeSql($stream, PHP_EOL);
            }
        } catch (\Throwable $exception) {
            fclose($stream);
            if (is_file($path)) {
                unlink($path);
            }
            throw $exception;
        }

        fclose($stream);

        return $path;
    }

    /**
     * @param  resource  $stream
     */
    private function writeSql($stream, string $sql): void
    {
        $length = strlen($sql);
        $written = 0;

        while ($written < $length) {
            $result = fwrite($stream, substr($sql, $written));
            if ($result === false || $result === 0) {
                throw new RuntimeException('The SQL export file could not be written.');
            }
            $written += $result;
        }
    }

    private function createTableStatement(string $table): string
    {
        $connection = DB::connection();
        $grammar = $connection->getQueryGrammar();
        $actualTable = $connection->getTablePrefix().$table;
        $driver = $connection->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $result = $connection->select('SHOW CREATE TABLE '.$grammar->wrap($actualTable));
            $statement = $result[0]->{'Create Table'} ?? null;
            if (! is_string($statement) || $statement === '') {
                throw new RuntimeException("The CREATE TABLE statement for [{$table}] could not be read.");
            }

            return preg_replace('/^CREATE TABLE /', 'CREATE TABLE IF NOT EXISTS ', $statement, 1)
                ?? throw new RuntimeException("The CREATE TABLE statement for [{$table}] could not be prepared.");
        }

        if ($driver === 'sqlite') {
            $result = $connection->select(
                "select sql from sqlite_master where type = 'table' and name = ?",
                [$actualTable],
            );
            $statement = $result[0]->sql ?? null;
            if (! is_string($statement) || $statement === '') {
                throw new RuntimeException("The CREATE TABLE statement for [{$table}] could not be read.");
            }

            return preg_replace('/^CREATE TABLE /', 'CREATE TABLE IF NOT EXISTS ', $statement, 1)
                ?? throw new RuntimeException("The CREATE TABLE statement for [{$table}] could not be prepared.");
        }

        throw new RuntimeException(
            'Including CREATE TABLE statements is currently supported only for MySQL, MariaDB, and SQLite.',
        );
    }

    private function wrapTable(string $table): string
    {
        return DB::connection()->getQueryGrammar()->wrapTable($table);
    }

    private function sqlValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        $quoted = DB::connection()->getPdo()->quote((string) $value);
        if ($quoted === false) {
            throw new RuntimeException('A database value could not be encoded in the SQL export.');
        }

        return $quoted;
    }
}
