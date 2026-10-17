<?php

namespace Database\Seeders;

use App\Models\FlowChart;
use App\Support\SopTemplate;
use Illuminate\Database\Seeder;

class SoftwareStructureSopSeeder extends Seeder
{
    public function run(): void
    {
        $examples = [
            [
                'category' => 'website',
                'title' => 'Website Structure & Build SOP',
                'subject_name' => 'Website architecture',
                'description' => 'Example structure for planning a maintainable web application from routes and pages through reusable UI, business logic, and data access.',
                'steps' => [
                    ['Start', 'Confirm users, goals, core journeys, and non-functional needs'],
                    ['Action', 'Map page hierarchy, navigation, and public versus authenticated routes'],
                    ['Action', 'Break pages into layouts, reusable components, and focused views'],
                    ['Action', 'Define controllers or application services for each business workflow'],
                    ['Action', 'Specify validation, authorization, and error handling at each boundary'],
                    ['Action', 'Plan API integrations, assets, and environment-based configuration'],
                    ['Action', 'Implement a vertical slice and add feature and component tests'],
                    ['Decision', 'Do the routes, UI, and tests cover the agreed journeys?'],
                    ['Action', 'Resolve coverage gaps, accessibility issues, and integration defects'],
                    ['End', 'Document the structure, setup steps, and deployment checks'],
                ],
                'connections' => [[1], [2], [3], [4], [5], [6], [7], [9, 8], [9], []],
            ],
            [
                'category' => 'process',
                'title' => 'Database Design & Migration SOP',
                'subject_name' => 'Database architecture',
                'description' => 'Example workflow for translating application requirements into a normalized schema, reviewed migrations, safe data changes, and verified queries.',
                'steps' => [
                    ['Start', 'Gather use cases, entities, lifecycle rules, and expected query patterns'],
                    ['Action', 'Identify entities, ownership, cardinality, and optional relationships'],
                    ['Action', 'Define columns, types, nullability, defaults, and domain constraints'],
                    ['Action', 'Normalize repeated data and choose stable primary and foreign keys'],
                    ['Action', 'Plan indexes around measured filters, joins, ordering, and uniqueness'],
                    ['Action', 'Write reversible migrations and explicit data backfills where required'],
                    ['Action', 'Create representative factories and seed only safe example data'],
                    ['Action', 'Test relationships, validation, deletion rules, and query behavior'],
                    ['Decision', 'Do tests and query plans satisfy correctness and performance needs?'],
                    ['Action', 'Revise schema, indexes, or migration strategy and retest'],
                    ['End', 'Document the data model, migration order, rollback limits, and deployment plan'],
                ],
                'connections' => [[1], [2], [3], [4], [5], [6], [7], [8], [10, 9], [10], []],
            ],
        ];

        foreach ($examples as $example) {
            FlowChart::query()->firstOrCreate(
                ['category' => $example['category'], 'title' => $example['title']],
                [
                    'is_example' => true,
                    'subject_name' => $example['subject_name'],
                    'description' => $example['description'],
                    'steps' => SopTemplate::customSteps($example['steps'], $example['connections']),
                ],
            );
        }
    }
}
