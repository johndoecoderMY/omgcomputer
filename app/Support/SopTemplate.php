<?php

namespace App\Support;

use Illuminate\Support\Str;

class SopTemplate
{
    public static function steps(string $category): array
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
            ], [[1], [2], [3], [4], [6, 5], [3], []]);
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

        return self::connectedSteps($labels, $connections);
    }

    public static function customSteps(array $labels, array $connections): array
    {
        return self::connectedSteps($labels, $connections);
    }

    private static function connectedSteps(array $labels, array $connections): array
    {
        $ids = array_map(fn (): string => (string) Str::uuid(), $labels);

        return array_map(
            fn (array $step, int $index): array => [
                'id' => $ids[$index],
                'label' => $step[1],
                'kind' => strtolower($step[0]),
                'connections' => array_map(fn (int $target): string => $ids[$target], $connections[$index]),
            ],
            $labels,
            array_keys($labels),
        );
    }
}
