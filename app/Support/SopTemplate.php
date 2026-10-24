<?php

namespace App\Support;

use Illuminate\Support\Str;

class SopTemplate
{
    public static function steps(string $category, array $existingSteps = []): array
    {
        if ($category === 'education') {
            return self::connectedSteps([
                ['Start', 'Agree the FYP scope, outcomes, and communication plan'],
                ['Action', 'Set up the project workspace, tools, repository, and milestones'],
                ['Action', 'Run regular consultations, mentoring, and technical clinics'],
                ['Action', 'Review progress, evidence, risks, and next actions'],
                ['Decision', 'Are the project outcomes and submission requirements met?'],
                ['Action', 'Address gaps and agree a revised action plan'],
                ['End', 'Prepare the final demonstration, report, and handover'],
            ], [[1], [2], [3], [4], [6, 5], [3], []], $existingSteps);
        }

        $labels = $category === 'product'
            ? [
                ['Start', 'Receive product or batch request'],
                ['Action', 'Confirm product specification, quantity, and required materials'],
                ['Action', 'Prepare or produce the item using the approved procedure'],
                ['Action', 'Inspect the item against quality and safety requirements'],
                ['Decision', 'Does the item pass inspection?'],
                ['Action', 'Fail: correct defects and repeat the inspection'],
                ['Action', 'Pass: label, package, and release the approved item'],
                ['End', 'Record the batch, inspection result, and release'],
            ]
            : [
                ['Start', 'Receive the service request and open a job ticket'],
                ['Action', 'Record customer, item, reported issue, and condition'],
                ['Action', 'Assess the request and prepare a written scope or quote'],
                ['Decision', 'Does the customer approve the scope or quote?'],
                ['Action', 'Perform the approved service and document work completed'],
                ['Action', 'Verify the result against service and safety requirements'],
                ['Decision', 'Does the completed service pass verification?'],
                ['Action', 'Fail: rework the service and verify it again'],
                ['Action', 'No approval: explain findings and return the item'],
                ['End', 'Record results, parts, customer handover, and applicable warranty'],
            ];

        $connections = $category === 'product'
            ? [[1], [2], [3], [4], [6, 5], [3], [7], []]
            : [[1], [2], [3], [4, 8], [5], [6], [9, 7], [5], [9], []];

        return self::connectedSteps($labels, $connections, $existingSteps);
    }

    public static function customSteps(array $labels, array $connections, array $existingSteps = []): array
    {
        return self::connectedSteps($labels, $connections, $existingSteps);
    }

    private static function connectedSteps(array $labels, array $connections, array $existingSteps = []): array
    {
        $ids = array_map(
            fn (array $step, int $index): string => (string) ($existingSteps[$index]['id'] ?? Str::uuid()),
            $labels,
            array_keys($labels),
        );
        $levels = [];
        $incoming = array_fill(0, count($labels), 0);

        foreach ($connections as $targets) {
            foreach ($targets as $target) {
                if (isset($incoming[$target])) {
                    $incoming[$target]++;
                }
            }
        }

        $placeComponent = function (int $start) use (&$levels, $connections, $labels): void {
            if (isset($levels[$start])) {
                return;
            }

            $queue = [$start];
            $levels[$start] = 0;
            for ($index = 0; $index < count($queue); $index++) {
                $source = $queue[$index];
                foreach ($connections[$source] ?? [] as $target) {
                    if (! array_key_exists($target, $labels) || isset($levels[$target])) {
                        continue;
                    }

                    $levels[$target] = $levels[$source] + 1;
                    $queue[] = $target;
                }
            }
        };

        foreach ($incoming as $index => $count) {
            if ($count === 0) {
                $placeComponent($index);
            }
        }
        foreach (array_keys($labels) as $index) {
            $placeComponent($index);
        }

        $rows = [];
        foreach (array_keys($labels) as $index) {
            $rows[$levels[$index]][] = $index;
        }

        $positions = [];
        foreach ($rows as $level => $row) {
            foreach ($row as $column => $index) {
                $positions[$index] = ['x' => $column * 250, 'y' => $level * 340];
            }
        }
        $oppositeSide = ['top' => 'bottom', 'bottom' => 'top', 'left' => 'right', 'right' => 'left'];

        return array_map(
            function (array $step, int $index) use ($ids, $connections, $positions, $oppositeSide): array {
                $targets = $connections[$index] ?? [];
                $connectionSides = [];
                foreach ($targets as $target) {
                    if (! isset($ids[$target], $positions[$target])) {
                        continue;
                    }

                    $dx = $positions[$target]['x'] - $positions[$index]['x'];
                    $dy = $positions[$target]['y'] - $positions[$index]['y'];
                    $sourceSide = abs($dx) > abs($dy)
                        ? ($dx >= 0 ? 'right' : 'left')
                        : ($dy >= 0 ? 'bottom' : 'top');
                    $connectionSides[$ids[$target]] = [
                        'source' => $sourceSide,
                        'target' => $oppositeSide[$sourceSide],
                    ];
                }

                return [
                    'id' => $ids[$index],
                    'label' => $step[1],
                    'kind' => strtolower($step[0]),
                    'connections' => array_map(fn (int $target): string => $ids[$target], $targets),
                    'connection_sides' => $connectionSides,
                ];
            },
            $labels,
            array_keys($labels),
        );
    }
}
