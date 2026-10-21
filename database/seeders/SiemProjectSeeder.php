<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Seeder;

class SiemProjectSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::query()->firstOrCreate(
            ['name' => 'SIEM'],
            [
                'description' => 'A beginner-friendly project to set up Wazuh and learn how a SIEM collects computer activity, shows alerts, and helps people respond.',
                'requirements_notes' => 'Build a small, safe lab using only computers you own or have permission to monitor. Start with one test computer. Follow the official Wazuh instructions for the version you choose. Do not test attacks against public or other people’s systems.',
            ],
        );

        $plan = [
            [
                'title' => 'Understand the project in simple words',
                'description' => 'Learn what the project does before installing anything. A SIEM is a central place to collect computer activity and show important events. Wazuh is the tool used for this project.',
                'subtasks' => [
                    'Write one sentence explaining what a SIEM does',
                    'Learn that an agent sends activity from a computer to the Wazuh server',
                    'Decide what you want to show in your final demonstration',
                ],
            ],
            [
                'title' => 'Plan a small and safe computer lab',
                'description' => 'Choose a small setup that your computer can handle. Only use computers that belong to you or that you have clear permission to monitor.',
                'subtasks' => [
                    'Check the available memory and free storage on your computer',
                    'Choose one test computer to monitor first',
                    'Write down which devices are in scope and get permission if needed',
                ],
            ],
            [
                'title' => 'Set up Wazuh and connect the test computer',
                'description' => 'Follow the official Wazuh installation guide for the selected version. The Wazuh server is the central receiver; an agent is installed on each computer you choose to monitor.',
                'subtasks' => [
                    'Install the Wazuh server in the lab',
                    'Install a Wazuh agent on the authorized test computer',
                    'Check the dashboard to confirm that the test computer is connected',
                ],
            ],
            [
                'title' => 'Read and explain the collected activity',
                'description' => 'Open the events in the dashboard and learn to identify which computer they came from and when they happened.',
                'subtasks' => [
                    'Find a recent event from the test computer',
                    'Write down the device name, event time, and what the event means',
                    'Save a screenshot for the project report',
                ],
            ],
            [
                'title' => 'Demonstrate safe file-change monitoring',
                'description' => 'Use a harmless sample folder and text file to show how Wazuh can report a file being added or changed.',
                'subtasks' => [
                    'Create a sample folder and text file on the test computer',
                    'Follow the Wazuh guide to monitor that sample folder',
                    'Change the sample file and find the resulting event in the dashboard',
                ],
            ],
            [
                'title' => 'Prepare the demonstration and final report',
                'description' => 'Explain what you built, show evidence that it works, and describe a sensible human response to an alert.',
                'subtasks' => [
                    'Write a simple response checklist: verify the alert, identify the device, inform the responsible person, and record the action',
                    'Include the lab setup, screenshots, results, and sources in the report',
                    'List limitations, costs, and what you would improve next',
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
                        'description' => 'Part of the beginner SIEM project plan.',
                        'status' => 'backlog',
                        'priority' => 'medium',
                    ],
                );
            }
        }
    }
}
