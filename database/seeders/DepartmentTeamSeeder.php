<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentTeamSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'Student Projects' => [
                'description' => 'Student FYP consultations, mentoring, and progress reviews.',
                'teams' => [
                    'FYP Mentoring',
                    'Progress Review',
                    'Project Setup Workshop',
                ],
            ],
            'Technology Services' => [
                'description' => 'Technical support, debugging, and practical technology clinics.',
                'teams' => [
                    'Debugging Support',
                    'Small-group Tech Clinic',
                ],
            ],
            'Operations' => [
                'description' => 'Coordination and delivery of internal services.',
                'teams' => [
                    'Service Coordination',
                ],
            ],
        ];

        foreach ($departments as $name => $details) {
            $department = Department::query()->firstOrCreate(
                ['name' => $name],
                ['description' => $details['description']],
            );

            foreach ($details['teams'] as $teamName) {
                $department->teams()->firstOrCreate(['name' => $teamName]);
            }
        }
    }
}
