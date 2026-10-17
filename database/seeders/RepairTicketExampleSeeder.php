<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\User;
use Illuminate\Database\Seeder;

class RepairTicketExampleSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::query()->firstOrCreate(
            ['name' => 'OMG Computer'],
            ['description' => 'Website, repair services, shop operations, and student technology services.'],
        );
        $userId = User::query()->orderBy('id')->value('id');

        $examples = [
            ['New', 'new', 'normal', 'Laptop', 'Acer Aspire 5', 'Laptop will not power on.', 'Customer reports no power after overnight charging.'],
            ['Diagnosis', 'diagnosing', 'high', 'Laptop', 'Dell Latitude 5420', 'Laptop overheats during normal use.', 'Inspect cooling fan and thermal system; confirm temperatures under load.'],
            ['Awaiting Customer', 'waiting_customer', 'normal', 'Phone', 'Samsung Galaxy A54', 'Phone screen has visible damage.', 'Waiting for customer approval of the display replacement quotation.'],
            ['Waiting Parts', 'waiting_parts', 'urgent', 'Laptop', 'Lenovo ThinkPad T14', 'Battery no longer holds charge.', 'Replacement battery requested from supplier; verify part compatibility before fitting.'],
            ['In Repair', 'in_repair', 'high', 'Desktop', 'Custom desktop PC', 'System crashes during startup.', 'Replace the failed power supply and run stability checks.'],
            ['Ready for Pickup', 'ready_for_pickup', 'normal', 'Laptop', 'HP Pavilion 15', 'Laptop keyboard has several unresponsive keys.', 'Keyboard replacement completed; final typing and function-key tests passed.'],
            ['Closed', 'closed', 'low', 'Tablet', 'iPad 9th generation', 'Tablet charging port is intermittent.', 'Charging port cleaned and verified with a sustained charging test.'],
            ['Cancelled', 'cancelled', 'low', 'Laptop', 'ASUS VivoBook 14', 'Laptop fan makes a rattling noise.', 'Customer cancelled the repair after receiving the estimate.'],
        ];

        foreach ($examples as $index => [$customerName, $status, $priority, $deviceType, $deviceModel, $issue, $notes]) {
            $sequence = $index + 1;
            $customer = Customer::query()->firstOrCreate(
                ['email' => sprintf('repair-example-%02d@example.test', $sequence)],
                [
                    'project_id' => $project->id,
                    'name' => 'Repair Example '.$customerName,
                    'phone' => '+601200000'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT),
                    'company' => 'Example customer',
                    'notes' => 'Seeded repair ticket example record.',
                ],
            );

            $ticket = RepairTicket::query()->firstOrCreate(
                ['ticket_number' => sprintf('RT-EXAMPLE-%04d', $sequence)],
                [
                    'customer_id' => $customer->id,
                    'project_id' => $project->id,
                    'assigned_to' => $userId,
                    'device_type' => $deviceType,
                    'device_model' => $deviceModel,
                    'serial_number' => sprintf('EXAMPLE-SN-%04d', $sequence),
                    'issue_description' => $issue,
                    'diagnosis' => $notes,
                    'status' => $status,
                    'priority' => $priority,
                    'due_at' => now()->addDays($sequence)->toDateString(),
                    'received_at' => now()->subDays(8 - $sequence),
                ],
            );

            $ticket->updates()->firstOrCreate(
                ['title' => 'Example intake and service notes'],
                [
                    'user_id' => $userId,
                    'status' => $status,
                    'notes' => $notes,
                ],
            );
        }
    }
}
