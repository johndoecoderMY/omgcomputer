<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Seeder;

class BusinessPlanTaskSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::query()->firstOrCreate(
            ['name' => 'OMG Computer'],
            ['description' => 'Website, repair services, shop operations, and student technology services.'],
        );

        $plan = [
            [
                'title' => 'Validate the repair shop location and requirements',
                'description' => 'Confirm the Kampar location, operating costs, business registration, and local licensing before committing capital.',
                'subtasks' => [
                    'Compare premises rent, utilities, deposits, and lease terms',
                    'Confirm business registration and local council requirements',
                    'Register OMG Computer with SSM',
                ],
            ],
            [
                'title' => 'Prepare repair services, pricing, and suppliers',
                'description' => 'Set a clear service menu and validate parts availability and startup costs with suppliers.',
                'subtasks' => [
                    'Confirm service scope and starting prices for repair and student support',
                    'Compare parts suppliers and estimate opening inventory costs',
                ],
            ],
            [
                'title' => 'Set up customer intake and repair operations',
                'description' => 'Prepare repeatable booking, device intake, approval, repair, and handover workflows.',
                'subtasks' => [
                    'Configure appointment booking and customer intake questions',
                    'Document repair approval, device handling, and customer handover steps',
                ],
            ],
            [
                'title' => 'Launch local marketing and student services',
                'description' => 'Introduce the shop to local customers and communicate ethical FYP consultation and mentoring services.',
                'subtasks' => [
                    'Prepare local launch materials and business profile information',
                    'Publish the student consultation and technical mentoring service menu',
                    'Register OMG Computer on Meta for business pages and social media',
                    'Register the shop on an e-commerce platform',
                    'Register for a payment gateway',
                ],
            ],
        ];

        foreach ($plan as $item) {
            $task = Task::query()->firstOrCreate(
                ['project_id' => $project->id, 'title' => $item['title']],
                [
                    'description' => $item['description'],
                    'status' => 'backlog',
                    'priority' => 'medium',
                ],
            );

            foreach ($item['subtasks'] as $subtaskTitle) {
                Task::query()->firstOrCreate(
                    [
                        'project_id' => $project->id,
                        'parent_task_id' => $task->id,
                        'title' => $subtaskTitle,
                    ],
                    [
                        'description' => 'Business plan implementation task.',
                        'status' => 'backlog',
                        'priority' => 'medium',
                    ],
                );
            }
        }
    }
}
