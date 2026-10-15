<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class OmgComputerProjectSeeder extends Seeder
{
    public function run(): void
    {
        Project::firstOrCreate(
            ['name' => 'OMG Computer'],
            ['description' => 'Website, repair services, shop operations, and student technology services.'],
        );
    }
}
