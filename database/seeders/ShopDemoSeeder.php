<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\InventoryPart;
use App\Models\ShopOrder;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ShopDemoSeeder extends Seeder
{
    public function run(): void
    {
        $staff = User::query()->where('email', 'test@example.com')->firstOrFail();
        $customers = Customer::query()
            ->whereIn('email', ['avery.tan@example.test', 'daniel.lim@example.test'])
            ->get()
            ->keyBy('email');
        $parts = InventoryPart::query()
            ->whereIn('sku', ['DEMO-CPU-001', 'DEMO-RAM-001', 'DEMO-NVME-001', 'DEMO-KB-001', 'DEMO-MOUSE-001'])
            ->get()
            ->keyBy('sku');

        $samples = [
            ['number' => 'SHOP-DEMO-ORDER-001', 'customer' => 'avery.tan@example.test', 'sku' => 'DEMO-CPU-001', 'status' => 'pending_payment', 'payment' => 'pending'],
            ['number' => 'SHOP-DEMO-ORDER-002', 'customer' => 'daniel.lim@example.test', 'sku' => 'DEMO-RAM-001', 'status' => 'paid', 'payment' => 'paid'],
            ['number' => 'SHOP-DEMO-ORDER-003', 'customer' => 'avery.tan@example.test', 'sku' => 'DEMO-NVME-001', 'status' => 'processing', 'payment' => 'paid'],
            ['number' => 'SHOP-DEMO-ORDER-004', 'customer' => 'daniel.lim@example.test', 'sku' => 'DEMO-KB-001', 'status' => 'completed', 'payment' => 'paid'],
            ['number' => 'SHOP-DEMO-ORDER-005', 'customer' => 'avery.tan@example.test', 'sku' => 'DEMO-MOUSE-001', 'status' => 'cancelled', 'payment' => 'failed'],
        ];

        foreach ($samples as $sample) {
            $customer = $customers->get($sample['customer']);
            $part = $parts->get($sample['sku']);
            if (! $customer || ! $part) {
                throw new \RuntimeException('Shop demo orders require the demo customers and products to be seeded first.');
            }

            $price = (float) $part->selling_price;
            $order = ShopOrder::query()->firstOrCreate(
                ['order_number' => $sample['number']],
                [
                    'uuid' => (string) Str::uuid(),
                    'customer_id' => $customer->id,
                    'user_id' => $staff->id,
                    'customer_name' => $customer->name,
                    'customer_email' => $customer->email,
                    'customer_phone' => $customer->phone,
                    'customer_address' => $customer->address,
                    'status' => $sample['status'],
                    'payment_status' => $sample['payment'],
                    'stock_reserved' => false,
                    'subtotal' => $price,
                    'total' => $price,
                    'currency' => 'MYR',
                    'notes' => 'Demo order seeded for shop UI previews.',
                    'paid_at' => $sample['payment'] === 'paid' ? now()->subDays(1) : null,
                ],
            );
            $order->items()->firstOrCreate(
                ['sku' => $part->sku],
                [
                    'inventory_part_id' => $part->id,
                    'name' => $part->name,
                    'quantity' => 1,
                    'unit_price' => $price,
                    'line_total' => $price,
                ],
            );
        }
    }
}
