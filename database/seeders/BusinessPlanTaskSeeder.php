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
                'status' => 'done',
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

        $completedSubtasks = [
            'Compare premises rent, utilities, deposits, and lease terms',
            'Confirm business registration and local council requirements',
            'Register OMG Computer with SSM',
            'Register OMG Computer on Meta for business pages and social media',
        ];

        foreach ($plan as $item) {
            $task = Task::query()->firstOrCreate(
                ['project_id' => $project->id, 'title' => $item['title']],
                [
                    'description' => $item['description'],
                    'status' => $item['status'] ?? 'backlog',
                    'priority' => 'medium',
                ],
            );

            if (($item['status'] ?? null) === 'done' && $task->status !== 'done') {
                $task->update(['status' => 'done']);
            }

            foreach ($item['subtasks'] as $subtaskTitle) {
                $isCompleted = in_array($subtaskTitle, $completedSubtasks, true);
                $description = $subtaskTitle === 'Register for a payment gateway'
                    ? 'Business plan implementation task. Selected payment gateway provider: Chips-In Asia.'
                    : 'Business plan implementation task.';
                $subtask = Task::query()->firstOrCreate(
                    [
                        'project_id' => $project->id,
                        'parent_task_id' => $task->id,
                        'title' => $subtaskTitle,
                    ],
                    [
                        'description' => $description,
                        'status' => $isCompleted ? 'done' : 'backlog',
                        'priority' => 'medium',
                    ],
                );

                if ($isCompleted && $subtask->status !== 'done') {
                    $subtask->update(['status' => 'done']);
                }

                if ($subtaskTitle === 'Register for a payment gateway' && $subtask->description !== $description) {
                    $subtask->update(['description' => $description]);
                }
            }
        }
    }
}
