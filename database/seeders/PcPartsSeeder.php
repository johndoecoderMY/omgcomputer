<?php

namespace Database\Seeders;

use App\Models\InventoryMovement;
use App\Models\InventoryPart;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class PcPartsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SupplierSeeder::class);

        $componentsSupplier = Supplier::query()->where('name', 'Demo PC Components Supply')->firstOrFail();
        $storageSupplier = Supplier::query()->where('name', 'Demo Storage and Memory Supply')->firstOrFail();
        $peripheralsSupplier = Supplier::query()->where('name', 'Demo Peripherals Supply')->firstOrFail();

        $parts = [
            ['sku' => 'DEMO-CPU-001', 'name' => 'Desktop processor', 'type' => 'cpu', 'supplier_id' => $componentsSupplier->id, 'cost_price' => 650, 'selling_price' => 799, 'stock_quantity' => 5, 'reorder_level' => 2],
            ['sku' => 'DEMO-GPU-001', 'name' => 'Graphics card', 'type' => 'gpu', 'supplier_id' => $componentsSupplier->id, 'cost_price' => 2200, 'selling_price' => 2599, 'stock_quantity' => 3, 'reorder_level' => 1],
            ['sku' => 'DEMO-RAM-001', 'name' => 'DDR5 memory kit 32GB', 'type' => 'ram', 'supplier_id' => $storageSupplier->id, 'cost_price' => 320, 'selling_price' => 399, 'stock_quantity' => 8, 'reorder_level' => 3],
            ['sku' => 'DEMO-MB-001', 'name' => 'ATX motherboard', 'type' => 'motherboard', 'supplier_id' => $componentsSupplier->id, 'cost_price' => 580, 'selling_price' => 699, 'stock_quantity' => 4, 'reorder_level' => 1],
            ['sku' => 'DEMO-NVME-001', 'name' => '512GB NVMe SSD', 'type' => 'storage', 'supplier_id' => $storageSupplier->id, 'cost_price' => 180, 'selling_price' => 260, 'stock_quantity' => 8, 'reorder_level' => 2],
            ['sku' => 'DEMO-HDD-001', 'name' => '2TB hard drive', 'type' => 'storage', 'supplier_id' => $storageSupplier->id, 'cost_price' => 210, 'selling_price' => 289, 'stock_quantity' => 4, 'reorder_level' => 2],
            ['sku' => 'DEMO-PSU-001', 'name' => '750W power supply', 'type' => 'power_supply', 'supplier_id' => $componentsSupplier->id, 'cost_price' => 350, 'selling_price' => 449, 'stock_quantity' => 3, 'reorder_level' => 1],
            ['sku' => 'DEMO-CASE-001', 'name' => 'Mid-tower PC case', 'type' => 'case', 'supplier_id' => $componentsSupplier->id, 'cost_price' => 280, 'selling_price' => 359, 'stock_quantity' => 2, 'reorder_level' => 1],
            ['sku' => 'DEMO-COOL-001', 'name' => 'Tower CPU air cooler', 'type' => 'cooling', 'supplier_id' => $componentsSupplier->id, 'cost_price' => 140, 'selling_price' => 199, 'stock_quantity' => 5, 'reorder_level' => 2],
            ['sku' => 'DEMO-FAN-001', 'name' => '120mm case fan', 'type' => 'cooling', 'supplier_id' => $componentsSupplier->id, 'cost_price' => 25, 'selling_price' => 39, 'stock_quantity' => 10, 'reorder_level' => 4],
            ['sku' => 'DEMO-KB-001', 'name' => 'USB keyboard', 'type' => 'peripheral', 'supplier_id' => $peripheralsSupplier->id, 'cost_price' => 55, 'selling_price' => 89, 'stock_quantity' => 6, 'reorder_level' => 2],
            ['sku' => 'DEMO-MOUSE-001', 'name' => 'USB optical mouse', 'type' => 'peripheral', 'supplier_id' => $peripheralsSupplier->id, 'cost_price' => 30, 'selling_price' => 49, 'stock_quantity' => 7, 'reorder_level' => 2],
            ['sku' => 'DEMO-MON-001', 'name' => '24-inch monitor', 'type' => 'peripheral', 'supplier_id' => $peripheralsSupplier->id, 'cost_price' => 420, 'selling_price' => 529, 'stock_quantity' => 2, 'reorder_level' => 1],
            ['sku' => 'DEMO-USB-001', 'name' => 'USB-C adapter', 'type' => 'other', 'supplier_id' => $peripheralsSupplier->id, 'cost_price' => 35, 'selling_price' => 59, 'stock_quantity' => 5, 'reorder_level' => 2],
        ];

        foreach ($parts as $partData) {
            $part = InventoryPart::query()->firstOrCreate(
                ['sku' => $partData['sku']],
                [
                    ...$partData,
                    'description' => 'Sample seeded PC component. Update details and stock to match your actual inventory.',
                ],
            );

            InventoryMovement::query()->firstOrCreate(
                ['reference' => 'SEED-OPENING-'.$part->sku],
                [
                    'inventory_part_id' => $part->id,
                    'type' => 'received',
                    'quantity_change' => $part->stock_quantity,
                    'quantity_after' => $part->stock_quantity,
                    'note' => 'Sample opening stock from the PC parts seeder.',
                ],
            );
        }
    }
}
