<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CsvPcPartsSeeder extends Seeder
{
    private const PRE_2018_CPU_ARCHITECTURES = [
        'bulldozer',
        'coffee lake',
        'core',
        'excavator',
        'haswell',
        'haswell refresh',
        'ivy bridge',
        'jaguar',
        'k10',
        'kaby lake',
        'lynx',
        'nehalem',
        'piledriver',
        'puma+',
        'sandy bridge',
        'skylake',
        'steamroller',
        'westmere',
        'wolfdale',
        'yorkfield',
        'zen',
    ];

    private const PART_TYPES = [
        'case-accessory' => 'other',
        'case-fan' => 'cooling',
        'case' => 'case',
        'cpu-cooler' => 'cooling',
        'cpu' => 'cpu',
        'external-hard-drive' => 'storage',
        'fan-controller' => 'cooling',
        'headphones' => 'peripheral',
        'internal-hard-drive' => 'storage',
        'keyboard' => 'peripheral',
        'memory' => 'ram',
        'monitor' => 'peripheral',
        'motherboard' => 'motherboard',
        'mouse' => 'peripheral',
        'optical-drive' => 'storage',
        'os' => 'operating_system',
        'power-supply' => 'power_supply',
        'sound-card' => 'other',
        'speakers' => 'peripheral',
        'thermal-paste' => 'cooling',
        'ups' => 'other',
        'video-card' => 'gpu',
        'webcam' => 'peripheral',
        'wired-network-card' => 'other',
        'wireless-network-card' => 'other',
    ];

    public function run(): void
    {
        $files = glob(storage_path('app/csv/*.csv'));
        if ($files === false || $files === []) {
            throw new RuntimeException('No PC part CSV files were found in storage/app/csv.');
        }

        sort($files);

        foreach ($files as $file) {
            $this->seedFile($file);
        }
    }

    private function seedFile(string $file): void
    {
        $source = pathinfo($file, PATHINFO_FILENAME);
        $type = self::PART_TYPES[$source] ?? null;
        if ($type === null) {
            throw new RuntimeException("No inventory type is configured for CSV file {$source}.csv.");
        }

        $handle = fopen($file, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Unable to read PC part CSV file {$source}.csv.");
        }

        try {
            $headers = fgetcsv($handle, null, ',', '"', '');
            if ($headers === false || $headers === [null]) {
                throw new RuntimeException("PC part CSV file {$source}.csv has no header row.");
            }

            $headers[0] = ltrim($headers[0], "\xEF\xBB\xBF");
            if (! in_array('name', $headers, true) || count($headers) !== count(array_unique($headers))) {
                throw new RuntimeException("PC part CSV file {$source}.csv must have unique headers including name.");
            }

            $batch = [];
            $excludedSkus = [];
            $recordNumber = 0;
            $rowNumber = 1;

            while (($fields = fgetcsv($handle, null, ',', '"', '')) !== false) {
                $rowNumber++;
                if ($fields === [null]) {
                    continue;
                }

                if (count($fields) !== count($headers)) {
                    throw new RuntimeException("Invalid column count in {$source}.csv on row {$rowNumber}.");
                }

                $record = array_combine($headers, $fields);
                if ($record === false || trim((string) $record['name']) === '') {
                    throw new RuntimeException("Missing product name in {$source}.csv on row {$rowNumber}.");
                }

                $recordNumber++;
                $sku = sprintf('CSV-%s-%06d', strtoupper($source), $recordNumber);
                if (! $this->isRelevantPart($source, $record)) {
                    $excludedSkus[] = $sku;
                    if (count($excludedSkus) === 500) {
                        $this->deleteExcludedParts($excludedSkus);
                        $excludedSkus = [];
                    }

                    continue;
                }

                $batch[] = [
                    'sku' => $sku,
                    'type' => $type,
                    'name' => $record['name'],
                    'description' => $this->formatDescription(basename($file), $headers, $record),
                    'catalog_price' => is_numeric($record['price'] ?? null) ? $record['price'] : null,
                    'catalog_currency' => 'USD',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($batch) === 100) {
                    $this->upsertBatch($batch);
                    $batch = [];
                }
            }

            if ($batch !== []) {
                $this->upsertBatch($batch);
            }
            if ($excludedSkus !== []) {
                $this->deleteExcludedParts($excludedSkus);
            }
        } finally {
            fclose($handle);
        }
    }

    private function isRelevantPart(string $source, array $record): bool
    {
        return match ($source) {
            'cpu' => ! in_array(
                strtolower(trim((string) ($record['microarchitecture'] ?? ''))),
                self::PRE_2018_CPU_ARCHITECTURES,
                true,
            ),
            'video-card' => ! $this->isPre2018GraphicsCard((string) ($record['chipset'] ?? '')),
            'motherboard' => $this->isCurrentMotherboard($record),
            'memory' => ! $this->isLegacyMemory($record),
            default => true,
        };
    }

    private function isPre2018GraphicsCard(string $chipset): bool
    {
        return preg_match('/\b(?:GTX|GT|GTS)\s*(?:[6-9]\d{2}|10\d{2})\b/i', $chipset) === 1
            || preg_match('/\bRadeon\s+(?:HD|R[3579]\s*\d{3}|RX\s*(?:[34]\d{2}|5[0-8]\d))\b/i', $chipset) === 1;
    }

    private function isCurrentMotherboard(array $record): bool
    {
        $socket = strtoupper(trim((string) ($record['socket'] ?? '')));
        if (in_array($socket, ['AM4', 'AM5', 'LGA1200', 'LGA1700', 'LGA1851', 'STRX4'], true)) {
            return true;
        }

        return $socket === 'LGA1151'
            && preg_match('/\b(?:B360|B365|H310|H370|Q370|Z370|Z390)(?:M|I)?\b/i', (string) ($record['name'] ?? '')) === 1;
    }

    private function isLegacyMemory(array $record): bool
    {
        return preg_match('/\bDDR[123]\b/i', (string) ($record['name'] ?? '')) === 1
            || preg_match('/^\s*[123]\s*,/', (string) ($record['speed'] ?? '')) === 1;
    }

    private function formatDescription(string $filename, array $headers, array $record): string
    {
        $lines = [
            "Source: {$filename} (prices are in USD).",
        ];

        foreach ($headers as $header) {
            $label = $header === 'price'
                ? 'Price (USD)'
                : ucfirst(str_replace('_', ' ', $header));
            $value = str_replace(["\r\n", "\r", "\n"], '\\n', (string) $record[$header]);
            $lines[] = "{$label}: {$value}";
        }

        return implode("\n", $lines);
    }

    private function upsertBatch(array $batch): void
    {
        DB::table('custom_pc_components')->upsert(
            $batch,
            ['sku'],
            ['name', 'type', 'description', 'catalog_price', 'catalog_currency', 'updated_at'],
        );
    }

    private function deleteExcludedParts(array $skus): void
    {
        DB::table('custom_pc_components')->whereIn('sku', $skus)->delete();
    }
}
