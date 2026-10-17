<?php

namespace Database\Seeders;

use App\Models\FlowChart;
use App\Support\SopTemplate;
use Illuminate\Database\Seeder;

class ServiceSopExampleSeeder extends Seeder
{
    public function run(): void
    {
        FlowChart::query()->firstOrCreate(
            [
                'category' => 'service',
                'title' => 'Laptop Repair Service SOP',
            ],
            [
                'is_example' => true,
                'subject_name' => 'Laptop repair service',
                'description' => 'Example reusable service SOP. Review and adapt the scope, approval, repair, verification, and handover steps for each service you offer.',
                'steps' => SopTemplate::steps('service'),
            ],
        );
    }
}
