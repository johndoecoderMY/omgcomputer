<?php

namespace Database\Seeders;

use App\Models\FlowChart;
use App\Support\SopTemplate;
use Illuminate\Database\Seeder;

class StudentFypSopSeeder extends Seeder
{
    public function run(): void
    {
        $examples = [
            [
                'FYP Consultation SOP',
                'Student consultations',
                'Agree an agenda, review evidence since the last meeting, unblock decisions, and leave with recorded actions.',
                [
                    ['Start', 'Student shares the agenda and current project evidence'],
                    ['Action', 'Review completed work, questions, and blockers'],
                    ['Decision', 'Is a supervisor decision or escalation needed?'],
                    ['Action', 'Agree options, advice, or an escalation owner'],
                    ['Action', 'Record decisions, owners, and due dates'],
                    ['End', 'Confirm the next consultation and student actions'],
                ],
                [[1], [2], [3, 4], [4], [5], []],
            ],
            [
                'FYP Debugging SOP',
                'Debugging support',
                'Guide students to reproduce and isolate a fault, form a testable hypothesis, and document a verified fix.',
                [
                    ['Start', 'Student states the expected and observed behaviour'],
                    ['Action', 'Capture the error, environment, and reproducible steps'],
                    ['Action', 'Isolate the smallest failing component and inspect evidence'],
                    ['Action', 'Form one hypothesis and run a controlled test'],
                    ['Decision', 'Did the test identify the root cause?'],
                    ['Action', 'Refine the hypothesis and repeat with new evidence'],
                    ['Action', 'Apply a fix and rerun relevant regression checks'],
                    ['End', 'Record the cause, fix, and remaining risks'],
                ],
                [[1], [2], [3], [4], [6, 5], [3], [7], []],
            ],
            [
                'FYP Mentoring SOP',
                'Student mentoring',
                'Set a student-led mentoring goal, discuss options and wellbeing or support needs, and agree a manageable next step.',
                [
                    ['Start', 'Student identifies a goal or current challenge'],
                    ['Action', 'Explore context, strengths, and options with open questions'],
                    ['Decision', 'Would specialist or wellbeing support be helpful?'],
                    ['Action', 'Agree a suitable referral or additional support'],
                    ['Action', 'Student chooses a realistic action and success measure'],
                    ['End', 'Record only appropriate notes and agree a check-in'],
                ],
                [[1], [2], [3, 4], [4], [5], []],
            ],
            [
                'FYP Project Setup Workshop SOP',
                'Project setup workshop',
                'Help students establish a clear project brief, safe workspace, version control, collaboration norms, and first milestones.',
                [
                    ['Start', 'Welcome participants and confirm workshop outcomes'],
                    ['Action', 'Clarify project scope, users, deliverables, and constraints'],
                    ['Action', 'Set up the repository, tools, access, and backup routine'],
                    ['Action', 'Agree team roles, communication, and working agreements'],
                    ['Action', 'Break the first milestone into testable tasks'],
                    ['Decision', 'Can each student access the tools and explain the next step?'],
                    ['Action', 'Resolve access or understanding gaps before closing'],
                    ['End', 'Share setup checklist and schedule the first review'],
                ],
                [[1], [2], [3], [4], [5], [7, 6], [7], []],
            ],
            [
                'FYP Progress Review SOP',
                'Progress reviews',
                'Review milestone evidence against the plan, identify risks early, and agree a realistic recovery or continuation plan.',
                [
                    ['Start', 'Student presents the milestone and evidence'],
                    ['Action', 'Compare actual progress with agreed scope and timeline'],
                    ['Action', 'Review quality, testing, documentation, and risks'],
                    ['Decision', 'Is the milestone on track and supported by evidence?'],
                    ['Action', 'Agree scope, support, or timeline changes for recovery'],
                    ['Action', 'Set the next milestone, owner, and review date'],
                    ['End', 'Record feedback, decisions, and student actions'],
                ],
                [[1], [2], [3], [5, 4], [5], [6], []],
            ],
            [
                'Small-Group FYP Tech Clinic SOP',
                'Small-group technical clinic',
                'Run a time-boxed peer clinic that protects project privacy, shares reproducible technical questions, and closes with individual actions.',
                [
                    ['Start', 'Set the clinic goal, time-box, and respectful ground rules'],
                    ['Action', 'Each student submits a concise, reproducible technical question'],
                    ['Action', 'Select one case and confirm the student consents to group discussion'],
                    ['Action', 'Facilitate peer ideas and test suggestions against evidence'],
                    ['Decision', 'Has the group produced a safe, testable next step?'],
                    ['Action', 'Time-box further investigation or schedule individual follow-up'],
                    ['End', 'Each student records their own action and closes shared materials'],
                ],
                [[1], [2], [3], [4], [6, 5], [6], []],
            ],
        ];

        foreach ($examples as [$title, $subject, $description, $steps, $connections]) {
            FlowChart::query()->firstOrCreate(
                ['category' => 'education', 'title' => $title],
                [
                    'is_example' => true,
                    'subject_name' => $subject,
                    'description' => $description,
                    'steps' => SopTemplate::customSteps($steps, $connections),
                ],
            );
        }
    }
}
