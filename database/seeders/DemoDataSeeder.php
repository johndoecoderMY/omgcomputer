<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\FlowChart;
use App\Models\InventoryMovement;
use App\Models\InventoryPart;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'support@omgcomputer.my')->firstOrFail();
        $project = Project::query()->firstOrCreate(
            ['name' => 'OMG Computer'],
            ['description' => 'Website, repair services, shop operations, and student technology services.'],
        );

        $part = InventoryPart::query()->firstOrCreate(
            ['sku' => 'DEMO-SSD-001'],
            [
                'name' => '512GB NVMe SSD',
                'description' => 'Sample stock item for quotations and invoices.',
                'cost_price' => 180,
                'selling_price' => 260,
                'stock_quantity' => 8,
                'reorder_level' => 2,
                'is_for_sale' => true,
            ],
        );
        $part->update(['is_for_sale' => true]);
        $project->inventoryParts()->syncWithoutDetaching([
            $part->id => [
                'quantity' => 1,
                'notes' => 'Example recommended hardware for storage expansion and testing.',
            ],
        ]);
        InventoryMovement::query()->firstOrCreate(
            ['reference' => 'SEED-OPENING-'.$part->id],
            [
                'inventory_part_id' => $part->id,
                'user_id' => $user->id,
                'type' => 'received',
                'quantity_change' => $part->stock_quantity,
                'quantity_after' => $part->stock_quantity,
                'note' => 'Sample opening stock.',
            ],
        );

        $rm2Products = [
            ['sku' => 'DEMO-RM2-001', 'name' => 'RM2 Demo Cable Tie', 'type' => 'other'],
            ['sku' => 'DEMO-RM2-002', 'name' => 'RM2 Demo USB Dust Plug', 'type' => 'peripheral'],
            ['sku' => 'DEMO-RM2-003', 'name' => 'RM2 Demo Screen Cloth', 'type' => 'other'],
            ['sku' => 'DEMO-RM2-004', 'name' => 'RM2 Demo Cable Label', 'type' => 'other'],
            ['sku' => 'DEMO-RM2-005', 'name' => 'RM2 Demo Mousepad', 'type' => 'peripheral'],
        ];

        foreach ($rm2Products as $product) {
            InventoryPart::query()->updateOrCreate(
                ['sku' => $product['sku']],
                [
                    'name' => $product['name'],
                    'type' => $product['type'],
                    'description' => 'Example RM2 shop product for testing checkout and payments.',
                    'cost_price' => 1,
                    'selling_price' => 2,
                    'stock_quantity' => 100,
                    'reorder_level' => 10,
                    'is_for_sale' => true,
                ],
            );
        }

        $customer = Customer::query()->firstOrCreate(
            ['email' => 'avery.tan@example.test'],
            [
                'project_id' => $project->id,
                'name' => 'Avery Tan',
                'phone' => '+60123456789',
                'company' => 'Kampar Campus',
                'notes' => 'Sample customer profile.',
            ],
        );
        $task = Task::query()->firstOrCreate(
            ['project_id' => $project->id, 'title' => 'Prepare repair booking workflow'],
            [
                'assigned_to' => $user->id,
                'description' => 'Sample assigned task for planner and team reports.',
                'status' => 'in_progress',
                'priority' => 'high',
                'due_date' => now()->addDays(7)->toDateString(),
            ],
        );

        $flowChart = FlowChart::query()->firstOrNew(
            ['title' => 'Sample repair booking journey'],
        );
        $flowChart->fill([
            'description' => 'Sample flow chart linked to a project task.',
            'steps' => [
                [
                    'id' => 'seed-booking-start',
                    'label' => 'Customer submits booking',
                    'kind' => 'start',
                    'connections' => ['seed-booking-review'],
                    'connection_sides' => ['seed-booking-review' => ['source' => 'bottom', 'target' => 'top']],
                ],
                [
                    'id' => 'seed-booking-review',
                    'label' => 'Review repair request',
                    'kind' => 'action',
                    'connections' => ['seed-booking-end'],
                    'connection_sides' => ['seed-booking-end' => ['source' => 'bottom', 'target' => 'top']],
                ],
                ['id' => 'seed-booking-end', 'label' => 'Confirm appointment', 'kind' => 'end', 'connections' => [], 'connection_sides' => []],
            ],
        ])->save();
        $task->update([
            'flow_chart_id' => $flowChart->id,
            'flow_chart_step_id' => 'seed-booking-review',
        ]);

    }
}
