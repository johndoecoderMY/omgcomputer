<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Project;
use Illuminate\Database\Seeder;

class OmgComputerProjectSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::firstOrCreate(
            ['name' => 'OMG Computer'],
            ['description' => 'Website, repair services, shop operations, and student technology services.'],
        );

        if (blank($project->requirements_notes)) {
            $project->update([
                'requirements_notes' => 'Initial launch scope for OMG Computer. Confirm business registration, licensing, provider choices, pricing, and payment processing requirements before launch.',
            ]);
        }

        foreach ([
            'Register OMG Computer with SSM and confirm local licensing requirements.',
            'Publish repair services, pricing, and appointment booking information.',
            'Create an e-commerce storefront with product listings and stock information.',
            'Set up and test a payment gateway, including refunds and transaction records.',
            'Create and verify Meta business profiles for Facebook and Instagram.',
            'Provide customers with repair intake, approval, status updates, and collection instructions.',
            'Protect customer information and document privacy and data-retention practices.',
            'Publish the student consultation and technical mentoring service scope.',
        ] as $sortOrder => $requirement) {
            $project->requirements()->firstOrCreate(
                ['requirement' => $requirement],
                ['sort_order' => $sortOrder],
            );
        }

        foreach ([
            ['name' => 'PHP', 'version' => '8.2', 'notes' => 'Runtime requirement in composer.json.'],
            ['name' => 'Laravel', 'version' => '11', 'notes' => 'Application framework.'],
            ['name' => 'Bootstrap', 'version' => '5', 'notes' => 'Falcon theme assets used for the interface.'],
            ['name' => 'Vite', 'version' => '7', 'notes' => 'Frontend asset build tooling.'],
            ['name' => 'mPDF', 'version' => '8.3', 'notes' => 'PDF generation for project and billing documents.'],
        ] as $technology) {
            $project->technologyStack()->firstOrCreate(
                ['name' => $technology['name']],
                [
                    'version' => $technology['version'],
                    'notes' => $technology['notes'],
                ],
            );
        }

        foreach ([
            [
                'description' => 'Domain registration and DNS (planning estimate; confirm registrar pricing)',
                'quantity' => 1,
                'unit_price' => 100,
            ],
            [
                'description' => 'Web hosting, SSL, and backups (first-year planning estimate; confirm provider pricing)',
                'quantity' => 1,
                'unit_price' => 900,
            ],
            [
                'description' => 'E-commerce platform subscription, 12 months (planning estimate; select a platform and confirm pricing)',
                'quantity' => 12,
                'unit_price' => 180,
            ],
            [
                'description' => 'Business email subscription, 12 months (planning estimate; confirm provider pricing)',
                'quantity' => 12,
                'unit_price' => 30,
            ],
            [
                'description' => 'Payment gateway setup and integration budget (planning estimate; confirm provider fees)',
                'quantity' => 1,
                'unit_price' => 500,
            ],
        ] as $estimate) {
            $project->estimateItems()->firstOrCreate(
                ['description' => $estimate['description']],
                [
                    'category' => 'software',
                    'quantity' => $estimate['quantity'],
                    'unit_price' => $estimate['unit_price'],
                ],
            );
        }

        foreach ([
            [
                'title' => 'Review SSM registration and local licensing',
                'days' => 3,
                'description' => 'Confirm registration steps, required documents, and local licensing for the Kampar shop.',
            ],
            [
                'title' => 'Choose e-commerce platform and payment gateway',
                'days' => 7,
                'description' => 'Compare platform features, subscription costs, supported payment methods, fees, and refund workflows.',
            ],
            [
                'title' => 'Review OMG Computer launch requirements',
                'days' => 14,
                'description' => 'Review the business registration, Meta setup, storefront, customer workflow, and launch checklist.',
            ],
            [
                'title' => 'Review launch budget and software estimates',
                'days' => 21,
                'description' => 'Replace planning estimates with confirmed quotes and approve the initial operating budget.',
            ],
        ] as $scheduleItem) {
            $startsAt = now()->startOfDay()->addDays($scheduleItem['days'])->setTime(10, 0);
            $project->events()->firstOrCreate(
                ['title' => $scheduleItem['title']],
                [
                    'event_type' => 'progress_review',
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->copy()->addHour(),
                    'location' => 'OMG Computer, Kampar',
                    'description' => $scheduleItem['description'],
                ],
            );
        }
    }
}
