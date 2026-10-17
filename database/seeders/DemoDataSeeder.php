<?php

namespace Database\Seeders;

use App\Models\BillingDocument;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\FlowChart;
use App\Models\InventoryMovement;
use App\Models\InventoryPart;
use App\Models\MarketingCampaign;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
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
            ],
        );
        $project->inventoryParts()->syncWithoutDetaching([
            $part->id => [
                'quantity' => 1,
                'notes' => 'Example recommended hardware for storage expansion and testing.',
            ],
        ]);
        InventoryMovement::query()->firstOrCreate(
            ['reference' => 'SEED-OPENING-' . $part->id],
            [
                'inventory_part_id' => $part->id,
                'user_id' => $user->id,
                'type' => 'received',
                'quantity_change' => $part->stock_quantity,
                'quantity_after' => $part->stock_quantity,
                'note' => 'Sample opening stock.',
            ],
        );

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
        $secondCustomer = Customer::query()->firstOrCreate(
            ['email' => 'daniel.lim@example.test'],
            [
                'project_id' => $project->id,
                'name' => 'Daniel Lim',
                'phone' => '+60129876543',
                'company' => 'Local customer',
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

        $flowChart = FlowChart::query()->firstOrCreate(
            ['title' => 'Sample repair booking journey'],
            [
                'description' => 'Sample flow chart linked to a project task.',
                'steps' => [
                    ['id' => 'seed-booking-start', 'label' => 'Customer submits booking', 'kind' => 'start', 'connections' => ['seed-booking-review']],
                    ['id' => 'seed-booking-review', 'label' => 'Review repair request', 'kind' => 'action', 'connections' => ['seed-booking-end']],
                    ['id' => 'seed-booking-end', 'label' => 'Confirm appointment', 'kind' => 'end', 'connections' => []],
                ],
            ],
        );
        $task->update([
            'flow_chart_id' => $flowChart->id,
            'flow_chart_step_id' => 'seed-booking-review',
        ]);

        $ticket = RepairTicket::query()->firstOrCreate(
            ['ticket_number' => 'RT-DEMO-001'],
            [
                'customer_id' => $customer->id,
                'project_id' => $project->id,
                'assigned_to' => $user->id,
                'device_type' => 'Laptop',
                'device_model' => 'ThinkPad T14',
                'serial_number' => 'DEMO-SN-001',
                'issue_description' => 'Battery is not charging reliably.',
                'diagnosis' => 'Sample ticket; inspect charging port and adapter.',
                'status' => 'diagnosing',
                'priority' => 'normal',
                'due_at' => now()->addDays(2)->toDateString(),
                'received_at' => now()->subDay(),
            ],
        );
        $ticket->updates()->firstOrCreate(
            ['title' => 'Device received'],
            [
                'user_id' => $user->id,
                'status' => 'diagnosing',
                'notes' => 'Sample SOP step: record device condition, verify the reported symptom, and document approval before repair.',
            ],
        );

        $quotation = BillingDocument::query()->firstOrCreate(
            ['number' => 'QUO-DEMO-001'],
            [
                'type' => 'quotation',
                'status' => 'draft',
                'user_id' => $user->id,
                'repair_ticket_id' => $ticket->id,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone,
                'issue_date' => now()->toDateString(),
                'subtotal' => 60,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'total' => 60,
                'paid_amount' => 0,
                'notes' => 'Sample draft quotation linked to repair ticket RT-DEMO-001.',
            ],
        );
        $quotation->items()->firstOrCreate(
            ['description' => 'Basic troubleshooting'],
            ['quantity' => 1, 'unit_price' => 60, 'line_total' => 60],
        );

        $invoice = BillingDocument::query()->firstOrCreate(
            ['number' => 'INV-DEMO-001'],
            [
                'type' => 'invoice',
                'status' => 'partial',
                'user_id' => $user->id,
                'repair_ticket_id' => $ticket->id,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone,
                'issue_date' => now()->subDays(2)->toDateString(),
                'due_date' => now()->addDays(12)->toDateString(),
                'subtotal' => 260,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'total' => 260,
                'paid_amount' => 100,
                'notes' => 'Sample part-paid invoice for report testing.',
            ],
        );
        $invoice->items()->firstOrCreate(
            ['description' => $part->name],
            [
                'inventory_part_id' => $part->id,
                'quantity' => 1,
                'unit_price' => 260,
                'line_total' => 260,
            ],
        );
        $invoice->payments()->firstOrCreate(
            ['reference' => 'SEED-PAYMENT-001'],
            ['user_id' => $user->id, 'amount' => 100, 'method' => 'bank_transfer', 'paid_at' => now()->subDay()],
        );

        MarketingCampaign::query()->firstOrCreate(
            ['name' => 'Sample student laptop care campaign'],
            [
                'channel' => 'social',
                'status' => 'active',
                'starts_on' => now()->subDays(3)->toDateString(),
                'ends_on' => now()->addDays(14)->toDateString(),
                'spend' => 80,
                'impressions' => 1800,
                'clicks' => 95,
                'leads' => 12,
                'conversions' => 3,
                'attributed_revenue' => 360,
            ],
        );

        $bookingDate = Carbon::now('Asia/Kuala_Lumpur')->addDays(3)->startOfDay();
        while ($bookingDate->isSunday()) {
            $bookingDate->addDay();
        }
        Booking::query()->firstOrCreate(
            ['reference' => 'BK-DEMO-001'],
            [
                'customer_id' => $secondCustomer->id,
                'customer_name' => $secondCustomer->name,
                'customer_email' => $secondCustomer->email,
                'customer_phone' => $secondCustomer->phone,
                'service_id' => 'laptop-cleaning',
                'service_name' => 'Laptop cleaning',
                'device_model' => 'Sample customer laptop',
                'request_notes' => 'Sample booking for testing the public calendar and staff queue.',
                'starts_at' => $bookingDate->setTime(11, 0)->setTimezone('UTC'),
                'duration_minutes' => 60,
                'status' => 'pending',
            ],
        );
    }
}